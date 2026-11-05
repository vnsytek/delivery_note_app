<?php

namespace Tests\Unit;

use App\Support\SearchText;
use App\Support\VietnameseAccentRestorer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VietnameseAccentRestorerTest extends TestCase
{
    private function restorer(): VietnameseAccentRestorer
    {
        return new VietnameseAccentRestorer(require __DIR__.'/../../config/accent_phrases.php');
    }

    #[DataProvider('suggestions')]
    public function test_suggest(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->restorer()->suggest($input));
    }

    public static function suggestions(): array
    {
        return [
            'hoa' => ['C?NG TY TNHH PHÚ L?M', 'CÔNG TY TNHH PHÚ LÂM'],
            'cụm nhiều từ' => ['C?NG TY C? PH?N S?N XU?T', 'CÔNG TY CỔ PHẦN SẢN XUẤT'],
            'thường, chữ đầu bị mất' => ['c?ng ty in ?n d?ch v?', 'công ty in ấn dịch vụ'],
            'chữ đầu thường sau từ thường' => ['in ?n', 'in ấn'],
            'có sẵn dấu' => ['Công ty TNHH Tân Xương', 'Công ty TNHH Tân Xương'],
            'chữ có dấu thường lẫn trong từ HOA' => ['GIàY', 'GIÀY'],
            'còn ? không đoán được' => ['NGUY?N ?', 'NGUY?N ?'],
            'rỗng' => ['', ''],
        ];
    }

    public function test_suggest_treats_null_as_empty(): void
    {
        $this->assertSame('', $this->restorer()->suggest(null));
    }

    public function test_status_of(): void
    {
        $r = $this->restorer();
        $this->assertSame('ok', $r->statusOf('Công ty', 'Công ty'));
        $this->assertSame('auto', $r->statusOf('C?NG TY', 'CÔNG TY'));
        $this->assertSame('partial', $r->statusOf('NGUY?N', 'NGUY?N'));
    }

    public function test_rejects_phrase_with_mismatched_word_count(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VietnameseAccentRestorer(['C?NG TY' => 'CÔNG']);
    }

    public function test_search_text_ignores_case_and_accents(): void
    {
        $this->assertSame('cong ty da nang', SearchText::normalize('CÔNG TY Đà Nẵng'));
        $this->assertSame('', SearchText::normalize(null));
    }
}
