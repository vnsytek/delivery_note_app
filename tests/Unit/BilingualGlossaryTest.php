<?php

namespace Tests\Unit;

use App\Services\BilingualGlossary;
use PHPUnit\Framework\TestCase;

class BilingualGlossaryTest extends TestCase
{
    private function glossary(): BilingualGlossary
    {
        return new BilingualGlossary(require __DIR__.'/../../config/glossary.php');
    }

    public function test_translates_known_vietnamese_words_and_keeps_codes(): void
    {
        $g = $this->glossary();

        $this->assertSame('1 卷/500 個', $g->toChinese('1 cuộn/500 pcs'));
        $this->assertSame('藍色 - 80x50x1000 標籤', $g->toChinese('XANH - 80x50x1000 tem'));
        $this->assertSame('灰色 (Grey)', $g->toChinese('Xám (Grey)'));
        $this->assertSame('無線滑鼠 Logitech M650', $g->toChinese('Chuột không dây Logitech M650')); // đảo thứ tự danh từ + tính từ
    }

    public function test_returns_the_original_when_nothing_matches(): void
    {
        $g = $this->glossary();

        $this->assertSame('LOGIB100', $g->toChinese('LOGIB100'));
        $this->assertSame('USB 3.0 to LAN 1Gbps', $g->toChinese('USB 3.0 to LAN 1Gbps')); // "to" tiếng Anh không bị dịch nhầm
        $this->assertSame('Nam Tài Việt Nam', $g->toChinese('Nam Tài Việt Nam'));
    }

    public function test_translates_chinese_back_to_vietnamese(): void
    {
        $g = $this->glossary();

        $this->assertSame('mực (vàng)', $g->toVietnamese('墨水(黄)'));
        $this->assertSame('kèm màn hình UPS', $g->toVietnamese('含屏幕 UPS'));
        $this->assertSame('băng mực mã vạch 80', $g->toVietnamese('條碼色帶80'));
        $this->assertSame('鬼才', $g->toVietnamese('鬼才')); // không có trong bảng thì giữ nguyên
    }
}
