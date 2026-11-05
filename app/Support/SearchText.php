<?php

namespace App\Support;

use Illuminate\Support\Str;

final class SearchText
{
    /** Bỏ dấu, đ -> d, chữ thường: để tìm kiếm không phân biệt hoa/thường, có/không dấu. */
    public static function normalize(?string $text): string
    {
        return Str::lower(Str::ascii($text ?? ''));
    }
}
