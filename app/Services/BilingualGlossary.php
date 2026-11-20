<?php

namespace App\Services;

use App\Support\SearchText;

/**
 * Dịch ngắn Việt ⇄ Trung cho phần quy cách của hàng hóa theo bảng từ trong config/glossary.php.
 * Chỉ thay những từ/cụm có trong bảng; mã hàng, model, số đo giữ nguyên. Không phải dịch máy: nếu không có từ nào
 * khớp thì trả về đúng chuỗi gốc để người gọi biết "chưa dịch được".
 */
class BilingualGlossary
{
    private const MAX_PHRASE_WORDS = 3;

    private const SEPARATORS = '/([\s()\.,;\-\/\+:]+)/u';

    /** @var array<string, string> */
    private array $terms;

    /** @var array<string, string> chữ Hán dài trước */
    private array $reverse;

    public function __construct(?array $glossary = null)
    {
        $glossary ??= config('glossary');
        $this->terms = $glossary['terms'];
        $this->reverse = $glossary['reverse'];
        uksort($this->reverse, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));
    }

    /** Việt -> Trung. Trả về chuỗi gốc nếu không có từ nào để thay. */
    public function toChinese(string $vietnamese): string
    {
        $parts = preg_split(self::SEPARATORS, $vietnamese, -1, PREG_SPLIT_DELIM_CAPTURE);
        $wordIndexes = [];
        for ($i = 0; $i < count($parts); $i += 2) {
            if ($parts[$i] !== '') {
                $wordIndexes[] = $i;
            }
        }

        $keys = array_map(fn (int $i) => SearchText::normalize($parts[$i]), $wordIndexes);
        $changed = false;
        for ($w = 0; $w < count($wordIndexes);) {
            $matched = false;
            for ($len = min(self::MAX_PHRASE_WORDS, count($wordIndexes) - $w); $len > 0; $len--) {
                $phrase = implode(' ', array_slice($keys, $w, $len));
                if (! isset($this->terms[$phrase]) || ! $this->adjacent($parts, $wordIndexes, $w, $len)) {
                    continue;
                }
                $parts[$wordIndexes[$w]] = $this->terms[$phrase];
                for ($k = 1; $k < $len; $k++) {
                    $parts[$wordIndexes[$w + $k]] = '';
                    $parts[$wordIndexes[$w + $k] - 1] = ''; // khoảng trắng nối các từ của cụm
                }
                $w += $len;
                $matched = $changed = true;
                break;
            }
            if (! $matched) {
                $w++;
            }
        }

        return $changed ? $this->tidy(implode('', $parts)) : $vietnamese;
    }

    /** Trung -> Việt. Trả về chuỗi gốc nếu không có cụm nào để thay. */
    public function toVietnamese(string $chinese): string
    {
        $out = $chinese;
        foreach ($this->reverse as $zh => $vi) {
            $out = str_replace($zh, ' '.$vi.' ', $out);
        }

        return $out === $chinese ? $chinese : $this->tidy($out);
    }

    /** Các từ của một cụm phải đứng liền nhau, chỉ cách bằng khoảng trắng. */
    private function adjacent(array $parts, array $wordIndexes, int $start, int $len): bool
    {
        for ($k = 1; $k < $len; $k++) {
            if (trim($parts[$wordIndexes[$start + $k] - 1]) !== '') {
                return false;
            }
        }

        return true;
    }

    private function tidy(string $text): string
    {
        $text = preg_replace('/(?<=\p{Han})\s+(?=\p{Han})/u', '', $text); // chữ Hán liền nhau không cần khoảng trắng
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = preg_replace('/\(\s+/', '(', $text);
        $text = preg_replace('/\s+\)/', ')', $text);

        return trim($text);
    }
}
