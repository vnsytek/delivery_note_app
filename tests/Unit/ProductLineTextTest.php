<?php

namespace Tests\Unit;

use App\Models\ProductName;
use App\Services\BilingualGlossary;
use App\Services\ProductLineText;
use App\Services\ProductTypeClassifier;
use App\Support\VietnameseAccentRestorer;
use PHPUnit\Framework\TestCase;

class ProductLineTextTest extends TestCase
{
    private function text(): ProductLineText
    {
        return new ProductLineText(
            new VietnameseAccentRestorer(require __DIR__.'/../../config/accent_phrases.php'),
            new ProductTypeClassifier(require __DIR__.'/../../config/product_types.php'),
            new BilingualGlossary(require __DIR__.'/../../config/glossary.php'),
        );
    }

    public function test_name_always_has_vietnamese_and_chinese(): void
    {
        $t = $this->text();

        // Tên Việt + chữ Hán có sẵn trong ERP.
        $a = $t->build('PRTL1210', 'Máy in phun màu Epson L1210 (In, A4, USB) 彩色打印机', '');
        $this->assertSame('Máy in phun màu Epson L1210 (In, A4, USB)', $a['name']);
        $this->assertSame(['彩色打印机', 'erp'], [$a['name_zh'], $a['name_zh_source']]);

        // Chưa có tên Trung => dùng tên loại thiết bị.
        $b = $t->build('NBB1403CVA-S66696W', 'INTEL Core 5-210H/16GB DDR5/ 1TB (512GBx2) SSD/Win11', 'B1403CVA');
        $this->assertSame(['筆記型電腦', 'type'], [$b['name_zh'], $b['name_zh_source']]);

        // Không nhận ra loại => vẫn có dòng Trung chung chung.
        $c = $t->build('ZZZ999', 'Hàng lạ', '');
        $this->assertSame(['貨品', 'fallback'], [$c['name_zh'], $c['name_zh_source']]);

        // ERP chỉ ghi chữ Hán => tên Việt lấy theo loại.
        $d = $t->build('CPUI7-14700', '处理器', '');
        $this->assertSame('CPU (vi xử lý)', $d['name']);
        $this->assertSame('处理器', $d['name_zh']);
    }

    public function test_spec_is_translated_both_ways_and_codes_are_left_alone(): void
    {
        $t = $this->text();

        $words = $t->build('GIT46IN', 'Giấy in tem', '1 cuộn/500 pcs');
        $this->assertSame(['1 cuộn/500 pcs', '1 卷/500 個'], [$words['spec'], $words['spec_zh']]);

        $chineseOnly = $t->build('MUC5000Y', 'Mực in phun BT-5000Y', '墨水(黄)');
        $this->assertSame(['mực (vàng)', '墨水(黄)'], [$chineseOnly['spec'], $chineseOnly['spec_zh']]);

        $code = $t->build('LOGIB100', 'Chuột quang LOGITECH B100', 'LOGIB100');
        $this->assertSame('LOGIB100', $code['spec']);
        $this->assertNull($code['spec_zh']); // chỉ là mã: in một lần

        $none = $t->build('X1', 'Hàng', '');
        $this->assertSame(['', null], [$none['spec'], $none['spec_zh']]);
    }

    public function test_catalog_entry_overrides_automatic_values(): void
    {
        $entry = new ProductName(['name' => 'Tên sửa tay', 'name_zh' => '手改品名', 'spec' => 'QC sửa', 'spec_zh' => '手改規格']);

        $r = $this->text()->build('NBB1', 'Laptop', 'B1', $entry);

        $this->assertSame(['Tên sửa tay', '手改品名', 'QC sửa', '手改規格'], [$r['name'], $r['name_zh'], $r['spec'], $r['spec_zh']]);
        $this->assertSame('catalog', $r['name_zh_source']);
        $this->assertSame('Laptop', $r['name_suggested']); // bản tự động vẫn có để so sánh khi lưu
    }
}
