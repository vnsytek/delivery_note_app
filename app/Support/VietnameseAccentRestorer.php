<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Khôi phục gợi ý dấu tiếng Việt cho dữ liệu ERP bị mất dấu (ký tự '?').
 *
 * Dữ liệu gốc lưu bằng bảng mã GBK nên các chữ như Ô, Ệ, Ư... đã bị thay bằng '?' từ lúc nhập.
 * Ở đây chỉ *đoán* theo từ điển cụm từ, nên kết quả là GỢI Ý - trừ những từ/cụm từ người đã xác nhận.
 */
class VietnameseAccentRestorer
{
    public const STATUS_OK = 'ok';

    public const STATUS_AUTO = 'auto';

    public const STATUS_PARTIAL = 'partial';

    private const SEPARATORS = '/([\s()\.,;\-\/]+)/u';

    /** @var array<string, list<string>> khóa = các từ HOA nối bằng khoảng trắng */
    private array $phrases = [];

    /** @var array<string, true> khóa của các mục đã được người xác nhận */
    private array $confirmed = [];

    private int $maxPhraseLength = 1;

    /**
     * @param  array<string, string>  $phrases  cụm từ mất dấu => cụm từ đúng
     * @param  list<string>  $confirmed  khóa (trong $phrases) đã được người xác nhận: chữ sửa theo các mục này coi là đúng
     */
    public function __construct(array $phrases, array $confirmed = [])
    {
        foreach ($phrases as $broken => $fixed) {
            $brokenWords = explode(' ', $broken);
            $fixedWords = explode(' ', $fixed);
            if (count($brokenWords) !== count($fixedWords)) {
                throw new InvalidArgumentException("Số từ hai vế khác nhau: {$broken}");
            }
            $this->phrases[$broken] = $fixedWords;
            $this->maxPhraseLength = max($this->maxPhraseLength, count($brokenWords));
        }
        $this->confirmed = array_fill_keys($confirmed, true);
    }

    /** Trả về chuỗi đã khôi phục dấu theo từ điển (có thể vẫn còn '?'). */
    public function suggest(?string $text): string
    {
        return $this->restore($text ?? '')[0];
    }

    /**
     * ok = không có '?', hoặc mọi từ lỗi đã được sửa theo mục người xác nhận; auto = đã gợi ý đủ; partial = vẫn còn '?'.
     */
    public function statusOf(string $original, string $fixed): string
    {
        if (! str_contains($original, '?')) {
            return self::STATUS_OK;
        }
        if (str_contains($fixed, '?')) {
            return self::STATUS_PARTIAL;
        }

        return $this->restore($original)[1] ? self::STATUS_OK : self::STATUS_AUTO;
    }

    /** @return list<string> các từ (bỏ dấu cách, dấu câu) theo đúng cách tách khi khôi phục */
    public static function words(string $text): array
    {
        return array_values(array_filter(preg_split(self::SEPARATORS, $text), fn (string $w) => $w !== ''));
    }

    /** @return array{0: string, 1: bool} chuỗi đã khôi phục, và mọi từ có '?' đều được sửa theo mục đã xác nhận hay không */
    private function restore(string $text): array
    {
        if ($text === '' || (! str_contains($text, '?') && ! preg_match('/[^\x00-\x7F]/', $text))) {
            return [$text, false];
        }

        $parts = preg_split(self::SEPARATORS, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $wordIndexes = [];
        for ($i = 0; $i < count($parts); $i += 2) {
            if ($parts[$i] !== '') {
                $wordIndexes[] = $i;
            }
        }

        $words = array_map(fn (int $i) => $parts[$i], $wordIndexes);
        $upper = array_map(fn (string $w) => mb_strtoupper($w), $words);
        $count = count($words);

        // Mỗi từ lấy theo cụm dài nhất phủ lên nó (cụm dài = nhiều ngữ cảnh hơn); bằng nhau thì ưu tiên cụm
        // đã xác nhận, rồi cụm bắt đầu trước. Các cụm được phép gối nhau: "L? M?C" và "M?C ??" cùng áp dụng.
        /** @var array<int, array{0: string, 1: int, 2: int}> vị trí => [khóa cụm, vị trí trong cụm, độ dài cụm] */
        $choice = [];
        for ($start = 0; $start < $count; $start++) {
            for ($len = min($this->maxPhraseLength, $count - $start); $len > 0; $len--) {
                $key = implode(' ', array_slice($upper, $start, $len));
                if (! isset($this->phrases[$key])) {
                    continue;
                }
                for ($k = 0; $k < $len; $k++) {
                    $current = $choice[$start + $k] ?? null;
                    if ($current === null || $len > $current[2]
                        || ($len === $current[2] && isset($this->confirmed[$key]) && ! isset($this->confirmed[$current[0]]))) {
                        $choice[$start + $k] = [$key, $k, $len];
                    }
                }
            }
        }

        $out = $words;
        $allConfirmed = true;
        for ($i = 0; $i < $count; $i++) {
            if (isset($choice[$i])) {
                [$key, $k] = $choice[$i];
                $out[$i] = $this->recase($words[$i], $this->phrases[$key][$k], $i > 0 ? $out[$i - 1] : '', $words[$i + 1] ?? '');
            }
            if (str_contains($words[$i], '?') && ! $this->isConfirmed($upper[$i], $choice[$i] ?? null, $out[$i])) {
                $allConfirmed = false;
            }
        }

        foreach ($wordIndexes as $position => $partIndex) {
            $parts[$partIndex] = $this->normalizeAccentCase($out[$position]);
        }

        return [implode('', $parts), $allConfirmed];
    }

    /** Từ lỗi được sửa theo cụm đã xác nhận, hoặc ra đúng kết quả của từ đơn đã xác nhận. */
    private function isConfirmed(string $upperWord, ?array $choice, string $result): bool
    {
        if ($choice !== null && isset($this->confirmed[$choice[0]])) {
            return true;
        }

        return isset($this->confirmed[$upperWord])
            && mb_strtoupper(implode(' ', $this->phrases[$upperWord])) === mb_strtoupper($result);
    }

    /** Áp kiểu hoa/thường của từ gốc lên từ mới. $prev / $next = từ liền trước / sau ('' nếu không có). */
    private function recase(string $orig, string $new, string $prev, string $next = ''): string
    {
        $letters = $this->asciiLetters($orig);
        if ($letters === '') {
            // Mất hết chữ ("??"): theo từ liền trước ("m?c ??" -> "mực đổ", "MàU ??" -> "MÀU ĐỎ").
            return preg_match('/[a-z]/', $this->asciiLetters($prev)) ? mb_strtolower($new) : mb_strtoupper($new);
        }
        if (! preg_match('/[a-z]/', $letters)) {
            // Chỉ còn một chữ HOA ("X?", "L?"): không biết là "XÃ" hay "Xã", theo từ bên cạnh
            // ("x? Minh L?ng, X? Th? Trì" -> "Xã Thị Trì"; "L? CN2" -> "LÔ CN2").
            $neighbour = $this->asciiLetters($prev !== '' ? $prev : $next);
            if (strlen($letters) === 1 && preg_match('/[a-z]/', $neighbour)) {
                return $this->capitalize($new);
            }

            return mb_strtoupper($new);
        }
        $rest = substr($letters, 1);
        if (ctype_upper($letters[0]) && ! preg_match('/[A-Z]/', $rest)) {
            return $this->capitalize($new);
        }
        if (! preg_match('/[A-Z]/', $letters)) {
            // Chữ đầu bị mất ('?') thì không biết hoa hay thường: coi là hoa
            // trừ khi từ liền trước viết thường hẳn (vd: "in ?n" -> "in ấn").
            if (str_starts_with($orig, '?') && ! $this->isLowercase($prev)) {
                return $this->capitalize($new);
            }

            return mb_strtolower($new);
        }

        return mb_strtoupper($new);
    }

    /** 'GIàY' -> 'GIÀY': chữ có dấu còn sót bị thường giữa từ toàn HOA. */
    private function normalizeAccentCase(string $token): string
    {
        $letters = $this->asciiLetters($token);
        if (! str_contains($token, '?') && strlen($letters) >= 2 && ! preg_match('/[a-z]/', $letters)) {
            return mb_strtoupper($token);
        }

        return $token;
    }

    private function asciiLetters(string $token): string
    {
        return preg_replace('/[^A-Za-z]/', '', $token);
    }

    private function capitalize(string $word): string
    {
        return mb_strtoupper(mb_substr($word, 0, 1)).mb_strtolower(mb_substr($word, 1));
    }

    private function isLowercase(string $word): bool
    {
        return $word !== '' && mb_strtolower($word) === $word && mb_strtoupper($word) !== $word;
    }
}
