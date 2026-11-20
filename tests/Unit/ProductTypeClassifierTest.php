<?php

namespace Tests\Unit;

use App\Services\ProductTypeClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductTypeClassifierTest extends TestCase
{
    private function classifier(): ProductTypeClassifier
    {
        return new ProductTypeClassifier(require __DIR__.'/../../config/product_types.php');
    }

    #[DataProvider('items')]
    public function test_classifies_real_erp_items(string $code, string $name, string $expected): void
    {
        $this->assertSame($expected, $this->classifier()->classify($code, $name)['key'] ?? null, "{$code} | {$name}");
    }

    public static function items(): array
    {
        return [
            'laptop theo mã (tên chỉ có cấu hình)' => ['NBB1403CVA-S66696W', 'INTEL Core 5-210H/16GB DDR5/ 1TB (512GBx2) SSD/Win11', 'laptop'],
            'màn hình theo mã' => ['MRVP227HF', 'MÀNHÌNHVI TÍNH(LCD) ASUS VP227HF 21.5"/FHD/VA/HDMI/VGA/3.', 'monitor'],
            'máy tính để bàn theo mã' => ['PCD700MF-0052250710', 'PC/U5-225/16GB/512G SSD/WIFI/330W ASUS/D', 'desktop'],
            'tai nghe qua mã LOA' => ['LOA8X228AA', '(Headset) Poly Blackwire 3220 Stereo USB-C Headset +USB-C/A', 'audio'],
            'ssd' => ['SSDMZ-77E500BW', 'SSD 500GB SAMSUNG 870 EVO SATA MZ-77E500', 'ssd'],
            'cpu' => ['CPUI5-12500BOX', 'Chip vi xử lý INTEL CORE i5 12500 + QUẠT', 'cpu'],
            'mực in' => ['MUCEP057-BLK', 'Mực in phun EPSON 057 BK (C13T09D100)', 'ink'],
            'camera' => ['CAMHDW2249T-S-IL', 'Camera Dahua DH-IPC-HDW2249T-S-IL', 'camera'],
            'dịch vụ sửa chữa' => ['SUACAM', 'Dịch vụ sửa chữa Cam', 'service'],
            'mã mang tên hãng => dựa vào tên: cáp' => ['UG40410', 'Cáp HDMI 2.0 dài 2m hỗ trợ 4K@60Hz Ugreen', 'cable'],
            'mã mang tên hãng => dựa vào tên: chuột' => ['LOGIM221', 'Chuột không dây Logitech M221 đen, Silent', 'mouse'],
            'mã mang tên hãng => dựa vào tên: sạc' => ['UGCD123', 'Sạc nhanh Ugreen 65W', 'charger'],
        ];
    }

    public function test_unknown_item_has_no_type(): void
    {
        $this->assertNull($this->classifier()->classify('ZZZ999', 'Hàng đặc biệt chưa phân loại'));
        $this->assertNull($this->classifier()->classify(null, null));
    }

    public function test_returns_bilingual_type_names(): void
    {
        $type = $this->classifier()->classify('NBB1403CVA', '');

        $this->assertSame('Máy tính xách tay (laptop)', $type['vi']);
        $this->assertSame('筆記型電腦', $type['zh']);
    }

    public function test_longest_code_prefix_wins(): void
    {
        $this->assertSame('psu', $this->classifier()->classify('PWCL750', '')['key']); // PWC, không phải PW... cùng loại nhưng khớp dài nhất
        $this->assertSame('desktop', $this->classifier()->classify('PCASUE300', 'ASUS WORKSTATION')['key']);
    }
}
