<?php

namespace Tests\Unit;

use App\Support\ChineseText;
use PHPUnit\Framework\TestCase;

class ChineseTextTest extends TestCase
{
    public function test_splits_han_runs_from_the_rest(): void
    {
        $this->assertSame(['latin' => 'SSD Ổ cứng M2', 'zh' => '硬盘'], ChineseText::split('SSD硬盘Ổ cứng M2'));
        $this->assertSame(
            ['latin' => 'Phí công sửa máy tính / Thay main board', 'zh' => '更换主板'],
            ChineseText::split('Phí công sửa máy tính / Thay main board / 更换主板'),
        );
        $this->assertSame(['latin' => 'Mực in phun', 'zh' => '墨水'], ChineseText::split('Mực in phun 墨水'));
    }

    public function test_plain_vietnamese_and_empty(): void
    {
        $this->assertSame(['latin' => 'Máy in', 'zh' => ''], ChineseText::split('Máy in'));
        $this->assertSame(['latin' => '', 'zh' => ''], ChineseText::split(null));
        $this->assertSame(['latin' => '', 'zh' => '处理器'], ChineseText::split('处理器'));
    }
}
