<?php

namespace App\Services;

use App\Models\AccentWord;
use App\Support\LostLetters;
use App\Support\VietnameseAccentRestorer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Từ điển sửa dấu theo từ, lưu trong MySQL của app (bảng accent_words), dùng chung cho mọi trang.
 *
 * Bộ khôi phục dấu = cụm từ viết sẵn (config/accent_phrases.php) + các từ trong bảng này đang được áp dụng
 * (đã xác nhận, hoặc app đoán chắc chắn). Từ đã xác nhận còn làm cho chữ hiện là "đúng" thay vì "gợi ý".
 */
class AccentDictionary
{
    private const CACHE_KEY = 'accent-dictionary.v1';

    /** @return array<string, string> cụm/từ HOA bị lỗi => dạng đúng HOA */
    public function phrases(): array
    {
        return $this->load()['phrases'];
    }

    /** @return array<string, string> chỉ các từ người đã xác nhận */
    public function confirmed(): array
    {
        return $this->load()['confirmed'];
    }

    public function restorer(): VietnameseAccentRestorer
    {
        return new VietnameseAccentRestorer($this->phrases(), array_keys($this->confirmed()));
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Người chọn dạng đúng cho một từ trên trang "Từ điển sửa dấu". */
    public function confirm(AccentWord $word, string $fixed): void
    {
        $word->update(['fixed' => mb_strtoupper(trim($fixed)), 'status' => AccentWord::CONFIRMED, 'source' => 'manual']);
        $this->forget();
    }

    /** Người chọn "bỏ qua" một từ: giữ nguyên '?'. */
    public function ignore(AccentWord $word): void
    {
        $word->update(['status' => AccentWord::IGNORED, 'source' => 'manual']);
        $this->forget();
    }

    /** Bỏ xác nhận / bỏ qua: trả từ về cho app tự đoán lại (lần chạy accent:suggest tiếp theo). */
    public function reset(AccentWord $word): void
    {
        $first = $word->candidates[0]['word'] ?? null;
        $word->update(['status' => AccentWord::UNSURE, 'source' => 'auto', 'fixed' => $first]);
        $this->forget();
    }

    /**
     * Học từ một lần sửa tay (tên khách hàng, tên hàng): ghép từng từ lỗi với từ đã sửa ở cùng vị trí.
     * Từ ngắn (1-2 chữ như "L?", "??") nghĩa đổi theo ngữ cảnh nên học kèm từ bên cạnh ("L? M?C" -> "LỌ MỰC").
     * Kết quả là "gợi ý" (áp dụng ngay, chờ xác nhận); không đè lên mục người đã xác nhận hoặc bỏ qua.
     *
     * @return int số từ/cụm học được
     */
    public function learn(?string $original, ?string $fixed, bool $refresh = true): int
    {
        $before = VietnameseAccentRestorer::words((string) $original);
        $after = VietnameseAccentRestorer::words((string) $fixed);
        if ($before === [] || count($before) !== count($after)) {
            return 0; // số từ khác nhau: không biết từ nào ứng với từ nào
        }

        $learned = 0;
        foreach ($before as $i => $word) {
            if (! str_contains($word, '?') || ! LostLetters::fits($word, $after[$i])) {
                continue;
            }
            $span = [$i];
            if (mb_strlen($word) <= 2) {
                $j = $i > 0 ? $i - 1 : $i + 1;
                if (! isset($before[$j]) || ! $this->matches($before[$j], $after[$j])) {
                    continue;
                }
                $span = $j < $i ? [$j, $i] : [$i, $j];
            }
            $broken = mb_strtoupper(implode(' ', array_map(fn ($k) => $before[$k], $span)));
            $entry = AccentWord::firstOrNew(['broken' => $broken]);
            if (in_array($entry->status, [AccentWord::CONFIRMED, AccentWord::IGNORED], true)) {
                continue;
            }
            $entry->fill([
                'fixed' => mb_strtoupper(implode(' ', array_map(fn ($k) => $after[$k], $span))),
                'status' => AccentWord::SUGGESTED,
                'source' => 'learned',
            ]);
            $entry->example ??= mb_substr(trim((string) $original), 0, 255);
            $entry->save();
            $learned++;
        }

        if ($learned > 0 && $refresh) {
            $this->forget();
        }

        return $learned;
    }

    /** Từ đã sửa có khớp từ gốc không: có '?' thì phải đúng khuôn, không có thì chỉ khác hoa/thường. */
    private function matches(string $original, string $fixed): bool
    {
        return str_contains($original, '?')
            ? LostLetters::fits($original, $fixed)
            : mb_strtoupper($original) === mb_strtoupper($fixed);
    }

    /** @return array{phrases: array<string, string>, confirmed: array<string, string>} */
    private function load(): array
    {
        $base = config('accent_phrases', []);
        try {
            $rows = Cache::rememberForever(self::CACHE_KEY, fn () => AccentWord::query()
                ->applied()
                ->get(['broken', 'fixed', 'status'])
                ->map(fn (AccentWord $w) => [$w->broken, $w->fixed, $w->status === AccentWord::CONFIRMED])
                ->all());
        } catch (QueryException) {
            return ['phrases' => $base, 'confirmed' => []]; // chưa chạy migrate
        }

        $phrases = $base;
        $confirmed = [];
        foreach ($rows as [$broken, $fixed, $isConfirmed]) {
            if (count(explode(' ', $broken)) !== count(explode(' ', $fixed))) {
                continue;
            }
            // Từ điển có sẵn chỉ là đoán: từ trong bảng (đã xem/đã học) được ưu tiên hơn.
            $phrases[$broken] = $fixed;
            if ($isConfirmed) {
                $confirmed[$broken] = $fixed;
            }
        }

        return ['phrases' => $phrases, 'confirmed' => $confirmed];
    }
}
