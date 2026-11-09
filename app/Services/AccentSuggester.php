<?php

namespace App\Services;

use App\Models\AccentWord;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteLine;
use App\Models\OrderLine;
use App\Models\ProductName;
use App\Support\LostLetters;
use App\Support\VietnameseAccentRestorer;

/**
 * Tự đoán dạng đúng cho mọi từ ERP bị mất dấu, ghi vào bảng accent_words (chỉ dữ liệu của app, không đụng ERP).
 *
 * Đoán một từ, ví dụ "VI?T":
 *  1. '?' chỉ có thể là chữ có dấu mà bảng mã GBK của ERP không có (LostLetters), nên chỉ những từ cùng
 *     "khuôn" vi?t mới là ứng viên: việt, viết... (viết thường mà vẫn mất chữ thì không thể là "í", "à"...).
 *  2. Ứng viên lấy từ danh sách tần suất từ tiếng Việt (vi-words.txt), cộng điểm lớn nếu là từ của ngành
 *     (vi-domain.txt) hoặc của chính công ty (chữ ERP còn đủ dấu, tên đã sửa tay, từ đã xác nhận).
 *  3. Ứng viên đầu hơn hẳn ứng viên thứ hai => "gợi ý" (áp dụng ngay); ngang nhau => "chưa chắc" (chờ người chọn).
 *
 * Đoán theo ngữ cảnh: với mỗi cặp hai từ liền nhau có '?', nếu khớp một cụm từ đã biết ("màu ??" ~ "màu đỏ",
 * "?? bàn" ~ "để bàn") và cụm đó sửa khác với từ đơn, tạo thêm mục cụm từ - bộ khôi phục ưu tiên cụm dài hơn.
 */
class AccentSuggester
{
    private const MAX_CANDIDATES = 6;

    /** @var array<string, array<string, int>> khuôn => [từ thường => tần suất]; tương tự cho khuôn lỏng / tổ hợp */
    private array $strict = [];

    private array $loose = [];

    private array $combining = [];

    /** @var array<string, int> từ thường của công ty => số lần gặp */
    private array $corpus = [];

    /** @var array<string, array<string, int>> từ thường của ngành => [loại chữ (address/company/product) => số dòng có từ đó] */
    private array $domain = [];

    /** @var array<string, array<string, float>> khuôn hai từ => [cụm hai từ thường => trọng số] */
    private array $pairs = [];

    /** @var array<string, array<string, int>> từ lỗi HOA => [từ đúng thường => số lần từ bên cạnh ủng hộ] */
    private array $support = [];

    /** @var array<string, string> từ đơn trong config/accent_phrases.php: HOA lỗi => HOA đúng */
    private array $configWords = [];

    public function __construct(private readonly AccentDictionary $dictionary) {}

    /** @return array{words: int, phrases: int, suggested: int, unsure: int, confirmed: int, learned: int} */
    public function run(): array
    {
        [$words, $bigrams, $clean] = $this->tokenize($this->erpTexts());
        $this->loadKnowledge($clean);
        $this->countContextSupport($bigrams);

        // 1. Từ đơn
        $existing = AccentWord::all()->keyBy('broken');
        foreach ($words as $broken => $info) {
            arsort($info['kinds']);
            $candidates = $this->candidates($broken, $info['lower'] >= $info['count'] / 2, (string) array_key_first($info['kinds']), $info['count']);
            $entry = $existing->get($broken) ?? new AccentWord(['broken' => $broken, 'source' => 'auto']);
            $entry->fill([
                'candidates' => $candidates,
                'occurrences' => $info['count'],
                'example' => mb_substr($info['example'], 0, 255),
            ]);
            if ($entry->source === 'auto' && $entry->status !== AccentWord::CONFIRMED) {
                [$fixed, $status] = $this->decide($candidates);
                if (! preg_match('/\p{L}/u', $broken)) {
                    $status = AccentWord::UNSURE; // "?", "??": chỉ đoán theo cụm từ, từ đơn quá mơ hồ
                }
                $entry->fill(['fixed' => $fixed, 'status' => $status]);
            }
            $entry->save();
        }

        // 2. Học từ các lần sửa tay (đè lên đoán tự động, không đè lên từ người đã xác nhận)
        $learned = $this->learnFromManualFixes();

        // 3. Cụm hai từ theo ngữ cảnh
        $entries = AccentWord::all()->keyBy('broken');
        $applied = $entries->filter(fn (AccentWord $w) => $w->isApplied())->map->fixed->all() + config('accent_phrases', []);
        $phraseKeys = [];
        foreach ($bigrams as $broken => $info) {
            $phrase = $this->bestPair($broken);
            if ($phrase === null || $this->wordsAlreadyGiveSame($broken, $phrase, $applied)) {
                continue;
            }
            $phraseKeys[] = $broken;
            $entry = $entries->get($broken) ?? new AccentWord(['broken' => $broken, 'source' => 'auto']);
            $entry->fill(['occurrences' => $info['count'], 'example' => mb_substr($info['example'], 0, 255)]);
            if ($entry->source === 'auto' && $entry->status !== AccentWord::CONFIRMED) {
                $entry->fill([
                    'fixed' => $phrase,
                    'status' => AccentWord::SUGGESTED,
                    'candidates' => [['word' => $phrase, 'score' => null]],
                ]);
            }
            $entry->save();
        }

        // 4. Dọn: bỏ mục tự đoán không còn xuất hiện; giữ mục người đã xác nhận / sửa / học.
        $seen = array_merge(array_keys($words), $phraseKeys);
        AccentWord::query()->where('source', 'auto')->whereNotIn('broken', $seen)->delete();
        foreach (AccentWord::query()->where('source', '!=', 'auto')->get() as $entry) {
            $entry->update(['occurrences' => $words[$entry->broken]['count'] ?? $bigrams[$entry->broken]['count'] ?? 0]);
        }

        $this->dictionary->forget();

        $active = AccentWord::query()->where('occurrences', '>', 0)->get(['broken', 'status']);
        $single = $active->filter(fn (AccentWord $w) => ! str_contains($w->broken, ' '));

        return [
            'words' => $single->count(),
            'phrases' => $active->count() - $single->count(),
            'suggested' => $single->where('status', AccentWord::SUGGESTED)->count(),
            'unsure' => $single->where('status', AccentWord::UNSURE)->count(),
            'confirmed' => $single->where('status', AccentWord::CONFIRMED)->count(),
            'learned' => $learned,
        ];
    }

    /**
     * Ứng viên cho một từ lỗi (HOA), điểm cao trước.
     *
     * @param  bool  $typedLowercase  từ này phần lớn được gõ thường: '?' không thể là chữ có trong GBK (à, í...)
     * @param  string  $kind  từ này chủ yếu nằm trong: address (địa chỉ) / company (tên công ty) / product (tên hàng)
     * @return list<array{word: string, score: float}>
     */
    public function candidates(string $broken, bool $typedLowercase = false, string $kind = '', int $occurrences = 0): array
    {
        $lower = mb_strtolower($broken);
        $scores = [];
        // Tần suất trong phụ đề phim chỉ là nền, cân nhẹ; từ của ngành / công ty mới quyết định.
        foreach ($this->strict[$lower] ?? [] as $word => $freq) {
            $scores[$word] = 0.6 * log(1 + $freq);
        }
        foreach ($this->combining[$lower] ?? [] as $word => $freq) {
            $scores[$word] ??= 0.6 * log(1 + $freq) - 4; // gõ kiểu tổ hợp: hiếm gặp
        }
        if (! $typedLowercase) {
            foreach ($this->loose[LostLetters::looseMask($lower)] ?? [] as $word => $freq) {
                $scores[$word] ??= 0.6 * log(1 + $freq) - 3; // chữ HOA có dấu cũng mất: ít khả năng hơn
            }
        }
        foreach (array_keys($scores) as $word) {
            $lines = $this->domain[$word] ?? [];
            if ($lines !== []) {
                $scores[$word] += (isset($lines[$kind]) ? 5 : 2) + log(1 + array_sum($lines));
            }
            $own = $this->corpus[$word] ?? 0;
            if ($own > 0) {
                $scores[$word] += 2 + log(1 + $own);
            }
            // Ngữ cảnh chỉ nặng ký khi giải thích được phần đáng kể số lần gặp: 12 lần "B? X?" (bộ xử lý)
            // không đủ để "X?" (390 lần, chủ yếu trong địa chỉ) thành "xử".
            $support = $this->support[$broken][$word] ?? 0;
            $share = $occurrences > 0 ? min(1, 5 * $support / $occurrences) : 1;
            $scores[$word] += log(1 + $support) * $share;
        }
        if (isset($this->configWords[$broken])) {
            $word = mb_strtolower($this->configWords[$broken]);
            $scores[$word] = ($scores[$word] ?? 0) + 6;
        }

        arsort($scores);
        $out = [];
        foreach (array_slice($scores, 0, self::MAX_CANDIDATES, true) as $word => $score) {
            $out[] = ['word' => mb_strtoupper((string) $word), 'score' => round($score, 2)];
        }

        return $out;
    }

    /**
     * @param  list<array{word: string, score: float}>  $candidates
     * @return array{0: ?string, 1: string}
     */
    private function decide(array $candidates): array
    {
        if ($candidates === []) {
            return [null, AccentWord::UNSURE];
        }
        $margin = isset($candidates[1]) ? $candidates[0]['score'] - $candidates[1]['score'] : INF;

        return [
            $candidates[0]['word'],
            $margin >= (float) config('accent.confidence_margin', 1.5) ? AccentWord::SUGGESTED : AccentWord::UNSURE,
        ];
    }

    /**
     * Ngữ cảnh: "B? NGU?N", "B? CHIA", "B? X?" đều khớp cụm đã biết với "bộ" => "bộ" được cộng điểm cho từ "B?".
     *
     * @param  array<string, array{count: int}>  $bigrams
     */
    private function countContextSupport(array $bigrams): void
    {
        foreach ($bigrams as $broken => $info) {
            $phrase = $this->bestPair($broken);
            if ($phrase === null) {
                continue;
            }
            $to = explode(' ', mb_strtolower($phrase));
            foreach (explode(' ', $broken) as $i => $word) {
                if (str_contains($word, '?')) {
                    $this->support[$word][$to[$i]] = ($this->support[$word][$to[$i]] ?? 0) + $info['count'];
                }
            }
        }
    }

    /** Cụm hai từ đã biết khớp với cặp từ lỗi (HOA), nếu có một cụm vượt trội. */
    private function bestPair(string $broken): ?string
    {
        $matches = $this->pairs[mb_strtolower($broken)] ?? [];
        if ($matches === []) {
            return null;
        }
        arsort($matches);
        $weights = array_values($matches);
        if (isset($weights[1]) && $weights[0] < 2 * $weights[1]) {
            return null; // hai cụm ngang nhau: không đoán
        }

        return mb_strtoupper((string) array_key_first($matches));
    }

    /** Các mục từ đơn đang áp dụng đã cho đúng kết quả của cụm => không cần thêm mục cụm từ. */
    private function wordsAlreadyGiveSame(string $broken, string $phrase, array $applied): bool
    {
        $from = explode(' ', $broken);
        $to = explode(' ', $phrase);
        foreach ($from as $i => $word) {
            $result = str_contains($word, '?') ? ($applied[$word] ?? null) : $word;
            if ($result !== $to[$i]) {
                return false;
            }
        }

        return true;
    }

    /** Học lại từ các bản sửa tay đã có (tên khách hàng, danh mục tên hàng). */
    private function learnFromManualFixes(): int
    {
        $learned = 0;
        $customers = Customer::query()
            ->where(fn ($q) => $q->whereNotNull('ma002_manual')->orWhereNotNull('ma003_manual'))
            ->get(['ma002', 'ma003', 'ma002_manual', 'ma003_manual']);
        foreach ($customers as $c) {
            $learned += $this->dictionary->learn($c->ma002, $c->ma002_manual, false);
            $learned += $this->dictionary->learn($c->ma003, $c->ma003_manual, false);
        }

        $names = ProductName::query()->pluck('name', 'item_code');
        if ($names->isNotEmpty()) {
            $erpNames = OrderLine::query()->whereIn('item_code', $names->keys())->pluck('erp_name', 'item_code')
                ->merge(DeliveryNoteLine::query()->whereIn('item_code', $names->keys())->pluck('erp_name', 'item_code'));
            foreach ($names as $code => $name) {
                $learned += $this->dictionary->learn($erpNames[$code] ?? null, $name, false);
            }
        }

        return $learned;
    }

    /** @return iterable<array{0: string, 1: ?string}> [loại chữ, đoạn chữ] của mọi chữ lấy từ ERP đang lưu trong app */
    private function erpTexts(): iterable
    {
        $sources = [
            'company' => [Customer::query()->pluck('ma002')],
            'address' => [Customer::query()->pluck('ma003'), DeliveryNote::query()->pluck('erp_address')],
            'product' => [
                DeliveryNoteLine::query()->pluck('erp_name'), DeliveryNoteLine::query()->pluck('erp_spec'),
                OrderLine::query()->pluck('erp_name'), OrderLine::query()->pluck('erp_spec'),
            ],
        ];
        foreach ($sources as $kind => $lists) {
            foreach ($lists as $list) {
                foreach ($list as $text) {
                    yield [$kind, $text];
                }
            }
        }
    }

    /**
     * Tách từ ERP thành: từ có '?', cặp hai từ có '?', và câu không có '?' (để học ngữ cảnh của công ty).
     *
     * @param  iterable<array{0: string, 1: ?string}>  $texts
     * @return array{0: array<string, array{count: int, lower: int, kinds: array<string, int>, example: string}>, 1: array<string, array{count: int, example: string}>, 2: list<string>}
     */
    private function tokenize(iterable $texts): array
    {
        $words = [];
        $bigrams = [];
        $clean = [];
        $maxUnknown = (int) config('accent.max_unknown_letters', 3);
        foreach ($texts as [$kind, $text]) {
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }
            if (! str_contains($text, '?')) {
                $clean[] = $text;

                continue;
            }
            $list = VietnameseAccentRestorer::words($text);
            foreach ($list as $i => $word) {
                if (! str_contains($word, '?') || ! $this->isWord($word)) {
                    continue;
                }
                if (substr_count($word, '?') <= $maxUnknown && preg_match('/\p{L}|^\?{1,2}$/u', $word)) {
                    $key = mb_strtoupper($word);
                    $words[$key] ??= ['count' => 0, 'lower' => 0, 'kinds' => [], 'example' => $text];
                    $words[$key]['count']++;
                    $words[$key]['kinds'][$kind] = ($words[$key]['kinds'][$kind] ?? 0) + 1;
                    $words[$key]['lower'] += preg_match('/[a-z]/', $word);
                }
                foreach ([$i - 1, $i + 1] as $j) {
                    if (isset($list[$j]) && $this->isWord($list[$j])) {
                        $pair = mb_strtoupper($j < $i ? $list[$j].' '.$word : $word.' '.$list[$j]);
                        if ($j < $i && str_contains($list[$j], '?')) {
                            continue; // cặp hai từ đều lỗi đã đếm ở từ trước
                        }
                        $bigrams[$pair] ??= ['count' => 0, 'example' => $text];
                        $bigrams[$pair]['count']++;
                    }
                }
            }
        }
        uasort($words, fn ($a, $b) => $b['count'] <=> $a['count']);

        return [$words, $bigrams, $clean];
    }

    /** Chỉ gồm chữ cái và '?' (không có số, ký hiệu, chữ Hán): mới là một từ tiếng Việt. */
    private function isWord(string $word): bool
    {
        return preg_match('/^[\p{L}?]+$/u', $word) && ! preg_match('/\p{Han}/u', $word);
    }

    /** Nạp danh sách từ, từ của ngành, từ và cụm từ của công ty. */
    private function loadKnowledge(array $cleanErpTexts): void
    {
        $domainLines = $this->lines((string) config('accent.domain'));
        $company = $cleanErpTexts;
        foreach (config('accent_phrases', []) as $broken => $fixed) {
            $company[] = $fixed;
            if (! str_contains($broken, ' ')) {
                $this->configWords[$broken] = $fixed;
            }
        }
        array_push(
            $company,
            ...Customer::query()->whereNotNull('ma002_manual')->pluck('ma002_manual')->all(),
            ...Customer::query()->whereNotNull('ma003_manual')->pluck('ma003_manual')->all(),
            ...ProductName::query()->pluck('name')->all(),
            ...AccentWord::query()->where('status', AccentWord::CONFIRMED)->whereNotNull('fixed')->pluck('fixed')->all(),
        );

        $kind = '';
        foreach ($domainLines as $line) {
            if (preg_match('/^\[(\w+)\]$/', $line, $m)) {
                $kind = $m[1];

                continue;
            }
            $list = $this->vietnameseWords($line);
            foreach (array_unique(array_diff($list, ['#'])) as $w) {
                $this->domain[$w][$kind] = ($this->domain[$w][$kind] ?? 0) + 1;
            }
            $this->addPairs($list, 3.0);
        }
        foreach ($company as $text) {
            $list = $this->vietnameseWords((string) $text);
            foreach ($list as $w) {
                if (preg_match('/[^\x00-\x7F]/', $w)) {
                    $this->corpus[$w] = ($this->corpus[$w] ?? 0) + 1;
                }
            }
            $this->addPairs($list, 1.0);
        }

        $vocabulary = [];
        foreach ($this->lines((string) config('accent.vocabulary')) as $line) {
            $parts = explode(' ', $line);
            if (count($parts) === 2 && preg_match('/^\p{L}+$/u', $parts[0])) {
                $word = mb_strtolower($parts[0]);
                $vocabulary[$word] = ($vocabulary[$word] ?? 0) + (int) $parts[1];
            }
        }
        foreach (array_keys($this->domain + $this->corpus) as $word) {
            $vocabulary[$word] ??= 0;
        }

        foreach ($vocabulary as $word => $freq) {
            $word = (string) $word;
            if (! preg_match('/[^\x00-\x7F]/', $word)) {
                continue; // không dấu: không bao giờ là đáp án cho một '?'
            }
            $mask = LostLetters::mask($word);
            if (str_contains($mask, '?')) {
                $this->strict[$mask][$word] = $freq;
            }
            $this->loose[LostLetters::looseMask($word)][$word] = $freq;
            $combining = LostLetters::combiningMask($word);
            if ($combining !== $mask) {
                $this->combining[$combining][$word] = $freq;
            }
        }
    }

    /** @param list<string> $words từ thường */
    private function addPairs(array $words, float $weight): void
    {
        for ($i = 0; $i + 1 < count($words); $i++) {
            if ($words[$i] === '#' || $words[$i + 1] === '#') {
                continue;
            }
            $key = LostLetters::mask($words[$i]).' '.LostLetters::mask($words[$i + 1]);
            if (str_contains($key, '?')) {
                $pair = $words[$i].' '.$words[$i + 1];
                $this->pairs[$key][$pair] = ($this->pairs[$key][$pair] ?? 0) + $weight;
            }
        }
    }

    /** @return list<string> các từ thường (chỉ gồm chữ cái) của một câu; từ có số/ký hiệu làm đứt cụm */
    private function vietnameseWords(string $text): array
    {
        return array_map(
            fn (string $w) => preg_match('/^\p{L}+$/u', $w) && ! preg_match('/\p{Han}/u', $w) ? mb_strtolower($w) : '#',
            VietnameseAccentRestorer::words($text),
        );
    }

    /** @return list<string> các dòng không rỗng, bỏ ghi chú '#' */
    private function lines(string $path): array
    {
        if ($path === '' || ! is_readable($path)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', file($path, FILE_IGNORE_NEW_LINES)),
            fn (string $l) => $l !== '' && ! str_starts_with($l, '#'),
        ));
    }
}
