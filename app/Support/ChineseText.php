<?php

namespace App\Support;

/** Tách chữ Hán lẫn trong tên hàng của ERP (vd "SSD硬盘Ổ cứng M2") thành phần tiếng Trung và phần còn lại. */
final class ChineseText
{
    private const RUN = '/[\p{Han}][\p{Han}\x{3000}-\x{303F}\x{FF00}-\x{FFEF}]*/u';

    /** @return array{latin: string, zh: string} latin = phần còn lại (tiếng Việt/mã), zh = các cụm chữ Hán nối bằng khoảng trắng */
    public static function split(?string $text): array
    {
        $text = (string) $text;
        preg_match_all(self::RUN, $text, $matches);

        $latin = preg_replace(self::RUN, ' ', $text);
        $latin = trim(preg_replace('/\s+/u', ' ', $latin), " /|\t");
        $latin = preg_replace('/\s*\/\s*(?=\/)/u', '', $latin); // "A / / B" -> "A / B"

        return ['latin' => trim($latin), 'zh' => implode(' ', $matches[0])];
    }
}
