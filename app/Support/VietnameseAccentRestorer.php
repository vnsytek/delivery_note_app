<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Khôi phục gợi ý dấu tiếng Việt cho dữ liệu ERP bị mất dấu (ký tự '?').
 *
 * Dữ liệu gốc lưu bằng bảng mã GBK nên các chữ như Ô, Ệ, Ư... đã bị thay bằng '?' từ lúc nhập.
 * Ở đây chỉ *đoán* theo từ điển cụm từ, nên kết quả là GỢI Ý.
 */
class VietnameseAccentRestorer
{
    public const STATUS_OK = 'ok';

    public const STATUS_AUTO = 'auto';

    public const STATUS_PARTIAL = 'partial';

    private const SEPARATORS = '/([\s()\.,;\-\/]+)/u';

    /** @var array<string, list<string>> khóa = các từ HOA nối bằng khoảng trắng */
    private array $phrases = [];

    private int $maxPhraseLength = 1;

    /** @param array<string, string> $phrases cụm từ mất dấu => cụm từ đúng */
    public function __construct(array $phrases)
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
    }

    /** Trả về chuỗi đã khôi phục dấu theo từ điển (có thể vẫn còn '?'). */
    public function suggest(?string $text): string
    {
        $text ??= '';
        if ($text === '' || (! str_contains($text, '?') && ! preg_match('/[^\x00-\x7F]/', $text))) {
            return $text;
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
        $out = $words;

        $count = count($words);
        for ($i = 0; $i < $count;) {
            $matched = false;
            for ($len = min($this->maxPhraseLength, $count - $i); $len > 0; $len--) {
                $key = implode(' ', array_slice($upper, $i, $len));
                if (! isset($this->phrases[$key])) {
                    continue;
                }
                foreach ($this->phrases[$key] as $j => $new) {
                    $prev = $i + $j > 0 ? $out[$i + $j - 1] : '';
                    $out[$i + $j] = $this->recase($words[$i + $j], $new, $prev);
                }
                $i += $len;
                $matched = true;
                break;
            }
            if (! $matched) {
                $i++;
            }
        }

        foreach ($wordIndexes as $position => $partIndex) {
            $parts[$partIndex] = $this->normalizeAccentCase($out[$position]);
        }

        return implode('', $parts);
    }

    /** ok = không có '?'; auto = đã gợi ý đủ; partial = vẫn còn '?'. */
    public function statusOf(string $original, string $fixed): string
    {
        if (! str_contains($original, '?')) {
            return self::STATUS_OK;
        }

        return str_contains($fixed, '?') ? self::STATUS_PARTIAL : self::STATUS_AUTO;
    }

    /** Áp kiểu hoa/thường của từ gốc lên từ mới. $prev = từ liền trước ('' nếu đầu chuỗi). */
    private function recase(string $orig, string $new, string $prev): string
    {
        $letters = $this->asciiLetters($orig);
        if ($letters === '' || ! preg_match('/[a-z]/', $letters)) {
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
