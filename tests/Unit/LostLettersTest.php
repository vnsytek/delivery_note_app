<?php

namespace Tests\Unit;

use App\Support\LostLetters;
use App\Support\VietnameseAccentRestorer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LostLettersTest extends TestCase
{
    public function test_mask_keeps_letters_that_gbk_can_store(): void
    {
        $this->assertSame('vi?t', LostLetters::mask('Việt'));
        $this->assertSame('giày', LostLetters::mask('GIÀY'));   // à có trong GBK: không mất
        $this->assertSame('?i?n', LostLetters::mask('điện'));
        $this->assertSame('ma?n', LostLetters::combiningMask('màn')); // gõ kiểu tổ hợp: dấu thanh thành '?'
        $this->assertSame('hiê?n', LostLetters::combiningMask('hiện'));
    }

    #[DataProvider('fits')]
    public function test_fits(string $broken, string $fixed, bool $expected): void
    {
        $this->assertSame($expected, LostLetters::fits($broken, $fixed));
    }

    public static function fits(): array
    {
        return [
            'đúng khuôn' => ['VI?T', 'VIỆT', true],
            'đủ chữ, khác hoa thường' => ['m?c', 'Mực', true],
            'chữ GBK có không thể thành ?' => ['vi?t', 'viét', false],
            'HOA có dấu cũng mất (khớp lỏng)' => ['GI?Y', 'GIÀY', true],
            'khác chữ còn nguyên' => ['VI?T', 'VIỆN', false],
            'khác độ dài' => ['VI?T', 'VIỆTT', false],
            'còn ?' => ['VI?T', 'VI?T', false],
            'gõ tổ hợp' => ['Ma?n', 'Màn', true],
        ];
    }

    public function test_confirmed_entries_count_as_correct(): void
    {
        $restorer = new VietnameseAccentRestorer(['VI?T' => 'VIỆT', 'M?C' => 'MỰC'], ['VI?T']);

        $this->assertSame('ok', $restorer->statusOf('Vi?t Nam', $restorer->suggest('Vi?t Nam')));
        $this->assertSame('auto', $restorer->statusOf('M?c in', $restorer->suggest('M?c in')));
        $this->assertSame('auto', $restorer->statusOf('Vi?t m?c', $restorer->suggest('Vi?t m?c')));
        $this->assertSame('partial', $restorer->statusOf('Vi?t ?', $restorer->suggest('Vi?t ?')));
    }

    public function test_words_splits_like_the_restorer(): void
    {
        $this->assertSame(['L?', '01', 'th?n', '??ng', 'Trang'], VietnameseAccentRestorer::words('L? 01, th?n ??ng Trang'));
    }
}
