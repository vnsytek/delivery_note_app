<?php

namespace Tests\Feature;

use App\Models\DeliveryNoteLine;
use App\Models\OrderLine;
use App\Models\ProductName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function line(array $attrs): DeliveryNoteLine
    {
        return DeliveryNoteLine::factory()->create($attrs);
    }

    public function test_page_requires_login(): void
    {
        $this->get('/products')->assertRedirect('/login');
        $this->put('/products', ['item_code' => 'X'])->assertRedirect('/login');
    }

    public function test_lists_products_and_opens_names_still_missing_accents_first(): void
    {
        $this->login();
        $this->line(['item_code' => 'LOGIMK120', 'erp_name' => 'B? bàn phím chuột Logitech MK120']);  // còn '?'
        $this->line(['item_code' => 'MUC1', 'erp_name' => 'C?NG TY in']);                                // từ điển sửa được
        OrderLine::factory()->create(['item_code' => 'APL1', 'erp_name' => 'MacBook Pro', 'erp_spec' => 'M?5']); // quy cách còn '?'

        // Mặc định mở tab "Còn dấu ?": chỉ mã còn '?' ở tên hoặc quy cách.
        $this->get('/products')->assertOk()
            ->assertSee('Tên hàng')->assertSee('LOGIMK120')->assertSee('APL1')->assertDontSee('MUC1');

        $this->get('/products?tab=all')->assertSee('MUC1')->assertSee('CÔNG TY in')
            ->assertDontSee('Quy cách ERP'); // không mã nào có quy cách ERP khác quy cách đang dùng
        // Tìm không dấu, không phân biệt hoa thường.
        $this->get('/products?tab=all&q=cong ty')->assertSee('MUC1')->assertDontSee('LOGIMK120');
    }

    public function test_saving_fixes_name_chinese_name_and_spec_everywhere_without_touching_erp_copy(): void
    {
        $this->login();
        $line = $this->line(['item_code' => 'APLMDE44SA/A', 'erp_name' => 'B? chia 打印机', 'erp_spec' => 'S?A-1']);

        $this->from('/products')->put('/products', [
            'item_code' => 'APLMDE44SA/A', 'name' => ' Bộ chia ', 'name_zh' => '分配器', 'spec' => 'SỬA-1',
        ])->assertRedirect('/products#p-'.substr(md5('APLMDE44SA/A'), 0, 10));

        $entry = ProductName::where('item_code', 'APLMDE44SA/A')->firstOrFail();
        $this->assertSame(['Bộ chia', '分配器', 'SỬA-1'], [$entry->name, $entry->name_zh, $entry->spec]);
        $this->assertSame('B? chia 打印机', $line->fresh()->erp_name, 'bản sao dữ liệu ERP giữ nguyên');

        // Bản in phiếu dùng tên / tên Trung / quy cách đã sửa.
        $this->get("/delivery-notes/{$line->delivery_note_id}/print")
            ->assertSee('Bộ chia')->assertSee('分配器')->assertSee('SỬA-1')->assertDontSee('打印机');
        $this->get('/products?tab=manual')->assertSee('APLMDE44SA/A')->assertSee('Quay về gợi ý');

        // Lưu phiếu giao (không gửi quy cách) không làm mất quy cách đã sửa.
        $this->put("/delivery-notes/{$line->delivery_note_id}", ['lines' => [$line->id => ['name' => 'Bộ chia', 'name_zh' => '']]]);
        $entry->refresh();
        $this->assertSame(['Bộ chia', null, 'SỬA-1'], [$entry->name, $entry->name_zh, $entry->spec]);

        // Quay về gợi ý: xóa phần sửa tay.
        $this->from('/products?tab=manual')->put('/products', ['item_code' => 'APLMDE44SA/A', 'action' => 'reset'])->assertRedirect();
        $this->assertDatabaseCount('product_names', 0);
    }

    public function test_values_equal_to_the_suggestion_are_not_stored(): void
    {
        $this->login();
        $this->line(['item_code' => 'A1', 'erp_name' => 'C?NG TY 打印机', 'erp_spec' => 'X1']);

        // Gửi lại đúng gợi ý (tên đã khôi phục dấu, tên Trung tách từ ERP, quy cách ERP) => không tạo mục.
        $this->from('/products')->put('/products', ['item_code' => 'A1', 'name' => 'CÔNG TY', 'name_zh' => '打印机', 'spec' => 'X1']);
        $this->assertDatabaseCount('product_names', 0);
    }

    public function test_only_known_item_codes_can_be_saved_and_input_is_validated(): void
    {
        $this->login();
        $this->line(['item_code' => 'A1']);

        $this->put('/products', ['item_code' => 'KHONG-CO', 'name' => 'x'])->assertNotFound();
        $this->from('/products')->put('/products', ['item_code' => 'A1', 'name' => str_repeat('a', 256)])->assertSessionHasErrors('name');
        $this->from('/products')->put('/products', ['name' => 'x'])->assertSessionHasErrors('item_code');
        $this->assertDatabaseCount('product_names', 0);
    }

    public function test_order_page_shows_chinese_name_and_fixed_spec(): void
    {
        $this->login();
        $line = OrderLine::factory()->create(['item_code' => 'PRT1', 'erp_name' => '打印机 Máy in', 'erp_spec' => 'L?3110']);
        ProductName::create(['item_code' => 'PRT1', 'spec' => 'LỖ3110']);

        $this->get("/orders/{$line->order_id}")->assertOk()
            ->assertSee('Máy in')->assertSee('打印机')->assertSee('quy cách LỖ3110', false);
    }
}
