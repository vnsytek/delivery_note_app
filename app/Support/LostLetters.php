<?php

namespace App\Support;

/**
 * Chữ nào có thể nằm sau một dấu '?' trong dữ liệu ERP.
 *
 * ERP lưu chữ theo bảng mã GBK (Chinese_PRC_BIN). GBK chỉ có vài chữ thường có dấu (bảng phiên âm
 * tiếng Trung): à á è é ê ì í ò ó ù ú. Mọi chữ tiếng Việt có dấu khác bị thay bằng '?' lúc nhập.
 * Vì vậy "VI?T" chỉ có thể là VIỆT/VIẾT..., không thể là "VIET" hay "VIÉT".
 */
final class LostLetters
{
    /** Chữ thường tiếng Việt có dấu mà GBK không có. */
    private const LOST = 'ảãạăằắẳẵặâầấẩẫậẻẽẹềếểễệỉĩịỏõọôồốổỗộơờớởỡợủũụưừứửữựỳýỷỹỵđ';

    /** Mỗi dòng: nguyên âm không thanh rồi 5 dạng có thanh (huyền, sắc, hỏi, ngã, nặng). */
    private const TONES = [
        'aàáảãạ', 'ăằắẳẵặ', 'âầấẩẫậ', 'eèéẻẽẹ', 'êềếểễệ', 'iìíỉĩị',
        'oòóỏõọ', 'ôồốổỗộ', 'ơờớởỡợ', 'uùúủũụ', 'ưừứửữự', 'yỳýỷỹỵ',
    ];

    /**
     * "Khuôn" của một từ: chữ thường, chữ bị mất trong ERP đổi thành '?'.
     * Từ đúng và từ ERP bị lỗi của nó có cùng khuôn: khuôn("việt") = "vi?t" = khuôn("VI?T").
     */
    public static function mask(string $word): string
    {
        static $map = null;
        $map ??= array_fill_keys(mb_str_split(self::LOST), '?');

        return strtr(mb_strtolower($word), $map);
    }

    /** Khuôn khi chữ gốc gõ HOA có dấu (À, Á... cũng không có trong GBK): mọi chữ có dấu đều thành '?'. */
    public static function looseMask(string $word): string
    {
        return preg_replace('/[^\x00-\x7F]/u', '?', mb_strtolower($word));
    }

    /**
     * Khuôn khi chữ gốc gõ kiểu "Unicode tổ hợp" (dấu thanh là ký tự riêng đứng sau nguyên âm):
     * dấu thanh thành một '?' riêng. Vd "màn hình" -> "ma?n hi?nh", "hiện" -> "hiê?n".
     */
    public static function combiningMask(string $word): string
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach (self::TONES as $row) {
                $letters = mb_str_split($row);
                foreach (array_slice($letters, 1) as $toned) {
                    $map[$toned] = self::mask($letters[0]).'?';
                }
            }
        }

        return self::mask(strtr(mb_strtolower($word), $map));
    }

    /** $fixed có thể là dạng đúng của $broken không (chữ còn nguyên khớp, '?' chỉ ứng với chữ/dấu bị mất)? */
    public static function fits(string $broken, string $fixed): bool
    {
        if (str_contains($fixed, '?')) {
            return false;
        }
        // Gõ chữ thường thì chữ có trong GBK (à, í...) không thể mất; chỉ gõ toàn HOA mới khớp lỏng.
        $allCaps = ! preg_match('/[a-z]/', $broken);
        $broken = mb_strtolower($broken);

        return self::mask($fixed) === $broken
            || ($allCaps && self::looseMask($fixed) === self::looseMask($broken))
            || self::combiningMask($fixed) === $broken;
    }
}
