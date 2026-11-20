<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteLine;
use App\Models\ProductName;
use App\Models\User;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryNotePagesTest extends TestCase
{
    use RefreshDatabase;

    private function note(array $attrs = [], int $lines = 1, array $lineAttrs = []): DeliveryNote
    {
        $note = DeliveryNote::factory()->create($attrs);
        for ($i = 1; $i <= $lines; $i++) {
            DeliveryNoteLine::factory()->create(array_merge(['delivery_note_id' => $note->id, 'seq' => sprintf('%03d', $i)], $lineAttrs));
        }

        return $note;
    }

    private function login(): void
    {
        $this->actingAs(User::factory()->create());
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $note = $this->note();
        $this->get('/delivery-notes')->assertRedirect('/login');
        $this->get("/delivery-notes/{$note->id}")->assertRedirect('/login');
        $this->get("/delivery-notes/{$note->id}/print")->assertRedirect('/login');
        $this->put("/delivery-notes/{$note->id}", ['receiver' => 'x'])->assertRedirect('/login');
    }

    public function test_index_lists_and_filters_notes(): void
    {
        $this->login();
        $customer = Customer::factory()->create(['ma001' => 'UDE', 'ma003' => 'C?NG TY UDE']);
        $a = $this->note(['erp_no' => '261028005', 'issued_on' => '2026-11-03', 'customer_code' => 'UDE']);
        $b = $this->note(['erp_no' => '260301001', 'issued_on' => '2026-03-01']);

        $this->get('/delivery-notes')->assertOk()->assertSee('V231-261028005')->assertSee('V231-260301001')->assertSee('CÔNG TY UDE');
        $this->get('/delivery-notes?q=V231-2610')->assertSee('V231-261028005')->assertDontSee('V231-260301001');
        $this->get('/delivery-notes?q=UDE')->assertSee('V231-261028005')->assertDontSee('V231-260301001');
        $this->get('/delivery-notes?from=2026-10-01')->assertSee('V231-261028005')->assertDontSee('V231-260301001');
        $this->get('/delivery-notes?from=2026-12-01&to=2026-01-01')->assertSessionHasErrors('to');
    }

    public function test_show_presents_restored_names_and_flags_unknown_unit(): void
    {
        $this->login();
        $this->seed(UnitSeeder::class);
        $note = $this->note([], 1, ['erp_name' => 'C?NG TY X', 'unit_raw' => 'CHIẾC']);
        DeliveryNoteLine::factory()->create(['delivery_note_id' => $note->id, 'seq' => '002', 'unit_raw' => 'thùng']);

        $response = $this->get("/delivery-notes/{$note->id}")->assertOk();
        $response->assertSee('CÔNG TY X')->assertSee('gợi ý')->assertSee('cái')->assertSee('thùng')->assertSee('lạ');
    }

    public function test_update_saves_manual_fields_and_product_names(): void
    {
        $this->login();
        $note = $this->note(['erp_address' => 'Hải Phòng'], 1, ['erp_name' => 'Muc in', 'item_code' => 'MUC1']);
        $line = $note->lines()->first();

        $this->put("/delivery-notes/{$note->id}", [
            'receiver' => ' Ms. Hien ', 'supplier_code' => 'NL0088', 'address_manual' => '', 'note' => 'Giao gấp',
            'lines' => [$line->id => ['name' => 'Mực in', 'dept_code' => 'MVP30', 'remark' => '']],
        ])->assertRedirect("/delivery-notes/{$note->id}");

        $note->refresh();
        $this->assertSame('Ms. Hien', $note->receiver);
        $this->assertSame('NL0088', $note->supplier_code);
        $this->assertNull($note->address_manual);
        $this->assertSame('Giao gấp', $note->note);
        $this->assertSame('MVP30', $line->fresh()->dept_code);
        $this->assertSame('Mực in', ProductName::where('item_code', 'MUC1')->value('name'));

        // Tên sửa được dùng lại cho phiếu khác cùng mã hàng.
        $other = $this->note([], 1, ['erp_name' => 'Muc in', 'item_code' => 'MUC1']);
        $this->get("/delivery-notes/{$other->id}")->assertSee('Mực in');
    }

    public function test_update_keeps_suggestion_out_of_catalog_and_blank_name_reverts(): void
    {
        $this->login();
        $note = $this->note([], 1, ['erp_name' => 'C?NG TY', 'item_code' => 'A1']);
        $line = $note->lines()->first();

        // Giữ nguyên gợi ý => không tạo mục danh mục.
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['name' => 'CÔNG TY']]])->assertRedirect();
        $this->assertDatabaseCount('product_names', 0);

        ProductName::create(['item_code' => 'A1', 'name' => 'Công ty sửa tay']);
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['name' => '']]])->assertRedirect();
        $this->assertDatabaseCount('product_names', 0);
    }

    public function test_update_ignores_lines_of_other_notes_and_validates(): void
    {
        $this->login();
        $note = $this->note();
        $foreign = $this->note()->lines()->first();

        $this->put("/delivery-notes/{$note->id}", ['lines' => [$foreign->id => ['dept_code' => 'HACK']]])->assertRedirect();
        $this->assertNull($foreign->fresh()->dept_code);

        $this->put("/delivery-notes/{$note->id}", ['receiver' => str_repeat('a', 101)])->assertSessionHasErrors('receiver');
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$foreign->id => ['name' => ['x']]]])->assertSessionHasErrors('lines.'.$foreign->id.'.name');
    }

    public function test_print_uses_erp_template_for_ude_like_the_photo(): void
    {
        $this->login();
        Customer::factory()->create(['ma001' => 'UDE', 'ma002' => 'UDE 創源', 'ma003' => 'CÔNG TY UDE']);
        $note = $this->note(['customer_code' => 'UDE', 'erp_no' => '261028005', 'issued_on' => '2026-11-03', 'receiver' => 'Ms. Hien - 0344536330', 'supplier_code' => 'NL0088'], 1, [
            'customer_po' => '33V2-261026009', 'customer_item_code' => 'VR-205140001', 'dept_code' => 'MVP30',
        ]);

        $this->get("/delivery-notes/{$note->id}/print")->assertOk()
            ->assertSee('class="sheet tpl-erp"', false)
            ->assertSee('css/print-erp.css', false)
            ->assertSeeInOrder(['上逸科技服務有限公司', 'CÔNG TY TNHH DỊCH VỤ VÀ KỸ THUẬT SANG Y', '303 Lê Duẩn, Kiến An, Hải Phòng', 'Phiếu Giao Hàng', '出貨單'])
            ->assertSeeInOrder(['销货单号', 'Số phiếu', 'V231- 261028005', '制表日期', 'Ngày lập', '2026-11-03'])
            ->assertSee('UDE 創源-')->assertSee('CÔNG TY UDE')
            ->assertSee('Ms. Hien - 0344536330')->assertSee('NL0088')
            ->assertSeeInOrder(['STT', 'MÃ LIỆU SANG Y', 'TÊN HÀNG', 'QUY CÁCH', 'SL', 'ĐVT', 'PO No.', 'Mã hàng UDE', 'Mã bộ phận'])
            ->assertSee('33V2-261026009')->assertSee('VR-205140001')->assertSee('MVP30')
            ->assertSee('客户签收')->assertSee('倉庫')
            ->assertDontSee('填表');
    }

    public function test_print_uses_excel_template_by_default(): void
    {
        $this->login();
        $customer = Customer::factory()->create(['ma001' => 'HDV', 'ma003' => 'CÔNG TY HDV']);
        $note = $this->note(['customer_code' => 'HDV', 'erp_no' => '260320002', 'issued_on' => '2026-03-20', 'erp_address' => 'Quảng Ninh'], 1, [
            'erp_name' => 'Máy in', 'erp_spec' => 'L3110', 'customer_po' => 'GB202603200001',
        ]);

        $this->get("/delivery-notes/{$note->id}/print")->assertOk()
            ->assertSee('class="sheet tpl-excel"', false)
            ->assertSee('css/print-excel.css', false)
            ->assertSeeInOrder(['Phiếu Giao Hàng', '出貨單'])
            ->assertSee('V231-260320002')->assertSee('20/03/2026')
            ->assertSee('HDV- CÔNG TY HDV')->assertSee('Quảng Ninh')
            ->assertSeeInOrder(['STT', 'MÃ LIỆU', 'TÊN HÀNG', 'QUY CÁCH', 'SL', 'ĐVT', '客單號', '备注'])
            ->assertSee('GB202603200001')
            ->assertSee('客户签收')->assertSee('倉庫')->assertSee('填表');
    }

    public function test_print_template_can_be_chosen_with_query_and_ignores_unknown_values(): void
    {
        $this->login();
        $note = $this->note();

        $this->get("/delivery-notes/{$note->id}/print?template=erp")->assertSee('tpl-erp', false);
        $this->get("/delivery-notes/{$note->id}/print?template=hack")->assertSee('tpl-excel', false)->assertDontSee('hack');
    }

    public function test_print_paginates_by_template_capacity(): void
    {
        $this->login();
        $excelCapacity = config('delivery_note.templates.excel.lines_per_page');
        $erpCapacity = config('delivery_note.templates.erp.lines_per_page');
        $note = $this->note([], max($excelCapacity, $erpCapacity) + 1);

        $excel = $this->get("/delivery-notes/{$note->id}/print?template=excel")->getContent();
        $erp = $this->get("/delivery-notes/{$note->id}/print?template=erp")->getContent();

        $this->assertSame((int) ceil(($erpCapacity + 1) / $excelCapacity), substr_count($excel, 'class="sheet tpl-excel"'));
        $this->assertSame(2, substr_count($erp, 'class="sheet tpl-erp"'));
        $this->assertStringContainsString('2/2', $erp);
    }

    public function test_print_page_escapes_user_input(): void
    {
        $this->login();
        $note = $this->note(['receiver' => '<script>alert(1)</script>']);

        foreach (['excel', 'erp'] as $template) {
            $this->get("/delivery-notes/{$note->id}/print?template={$template}")
                ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
        }
    }

    public function test_print_shows_chinese_name_under_vietnamese_in_both_templates(): void
    {
        $this->login();
        // Chữ Hán lẫn trong tên ERP tự được tách xuống dòng dưới; mục danh mục có tên Trung thì ưu tiên.
        $note = $this->note([], 1, ['item_code' => 'A1', 'erp_name' => 'SSD硬盘Ổ cứng M2']);
        DeliveryNoteLine::factory()->create(['delivery_note_id' => $note->id, 'seq' => '002', 'item_code' => 'CPU1', 'erp_name' => 'Core i7-14700']);
        ProductName::create(['item_code' => 'CPU1', 'name' => null, 'name_zh' => '处理器']);

        foreach (['excel', 'erp'] as $template) {
            $this->get("/delivery-notes/{$note->id}/print?template={$template}")
                ->assertSeeInOrder(['SSD Ổ cứng M2', '硬盘'])
                ->assertSeeInOrder(['Core i7-14700', '处理器'])
                ->assertDontSee('SSD硬盘');
        }
    }

    public function test_update_saves_chinese_name_without_freezing_the_vietnamese_suggestion(): void
    {
        $this->login();
        $note = $this->note([], 1, ['erp_name' => 'Core i7-14700', 'item_code' => 'CPU1']);
        $line = $note->lines()->first();

        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['name' => 'Core i7-14700', 'name_zh' => ' 处理器 ']]])->assertRedirect();
        $this->assertDatabaseHas('product_names', ['item_code' => 'CPU1', 'name' => null, 'name_zh' => '处理器']);
        $this->get("/delivery-notes/{$note->id}")->assertSee('处理器')->assertDontSee('đã sửa tay');

        // Xóa tên Trung và tên Việt không đổi => mục danh mục bị xóa.
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['name' => 'Core i7-14700', 'name_zh' => '']]])->assertRedirect();
        $this->assertDatabaseCount('product_names', 0);
    }

    public function test_erp_general_template_has_general_columns_and_both_sign_boxes(): void
    {
        $this->login();
        $note = $this->note(['erp_no' => '261111001', 'issued_on' => '2026-11-11', 'receiver' => 'Ms. Lan', 'erp_address' => 'Hải Phòng'], 1, [
            'customer_po' => 'SG2026111001', 'customer_item_code' => 'XH1874', 'dept_code' => 'MVP30', 'remark' => 'Giao gấp',
            'erp_name' => 'Máy in phun màu Epson L1210', 'erp_spec' => 'L3110',
        ]);

        $this->get("/delivery-notes/{$note->id}/print?template=erp-general")->assertOk()
            ->assertSee('class="sheet tpl-erp tpl-erp-general"', false)
            ->assertSee('css/print-erp.css', false)
            ->assertSeeInOrder(['Phiếu Giao Hàng', '出貨單', 'V231- 261111001'])
            ->assertSeeInOrder(['客户名稱', '收货人:', 'Ms. Lan', '收货地址', 'Hải Phòng'])
            ->assertSeeInOrder(['STT', 'MÃ LIỆU', 'TÊN HÀNG', 'QUY CÁCH', 'SL', 'ĐVT', 'Số đơn khách', 'Mã hàng khách', 'Ghi chú'])
            ->assertSeeInOrder(['客戶品號', 'Mã hàng khách'])
            ->assertSeeInOrder(['TÊN HÀNG', 'QUY CÁCH', 'Máy in phun màu Epson L1210', 'L3110'])
            ->assertSee('SG2026111001')->assertSee('Giao gấp')
            ->assertSeeInOrder(['客户签收', '倉庫', '填表'])
            ->assertSeeInOrder(['SG2026111001', 'XH1874', 'Giao gấp']) // 客戶品號 (COPTD.TD014) nằm cạnh số đơn khách
            // mã bộ phận chỉ có ở mẫu ERP riêng
            ->assertDontSee('Mã bộ phận')->assertDontSee('MVP30');

        $this->get("/delivery-notes/{$note->id}/print?template=erp")
            ->assertSee('Mã bộ phận')->assertSee('XH1874')->assertDontSee('填表');
    }

    public function test_template_switcher_lists_general_and_specific_erp_templates(): void
    {
        $this->login();
        $note = $this->note();

        $this->get("/delivery-notes/{$note->id}")->assertSeeInOrder(['Mẫu Excel', 'Mẫu ERP chung', 'Mẫu ERP riêng']);
        $this->get("/delivery-notes/{$note->id}/print")->assertSee('Mẫu ERP chung')->assertSee('Mẫu ERP riêng');
    }

    public function test_every_template_prints_name_and_spec_in_vietnamese_and_chinese(): void
    {
        $this->login();
        $note = $this->note([], 1, ['item_code' => 'GIT46IN', 'erp_name' => 'Giấy in tem', 'erp_spec' => '1 cuộn/500 pcs']);

        foreach (['excel', 'erp-general', 'erp'] as $template) {
            $this->get("/delivery-notes/{$note->id}/print?template={$template}")
                ->assertSeeInOrder(['Giấy in tem', '標籤紙／熱感紙'])   // tên Việt rồi tên Trung (loại hàng)
                ->assertSeeInOrder(['1 cuộn', '1 卷/500 個'], false);    // quy cách Việt rồi quy cách Trung
        }
    }

    public function test_update_saves_spec_in_both_languages_and_only_what_differs_from_automatic(): void
    {
        $this->login();
        $note = $this->note([], 1, ['item_code' => 'GIT46IN', 'erp_name' => 'Giấy in tem', 'erp_spec' => '1 cuộn/500 pcs']);
        $line = $note->lines()->first();

        // Quy cách Việt giữ nguyên bản tự động (không lưu), quy cách Trung sửa tay.
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => [
            'name' => 'Giấy in tem', 'name_zh' => '標籤紙／熱感紙', 'spec' => '1 cuộn/500 pcs', 'spec_zh' => '每卷500張',
        ]]])->assertRedirect();

        $this->assertDatabaseHas('product_names', ['item_code' => 'GIT46IN', 'name' => null, 'name_zh' => null, 'spec' => null, 'spec_zh' => '每卷500張']);
        $this->get("/delivery-notes/{$note->id}/print?template=erp-general")->assertSee('每卷500張')->assertDontSee('1 卷/500 個');

        // Xóa bản sửa => quay về bản tự động và mục danh mục bị bỏ.
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['spec_zh' => '1 卷/500 個']]])->assertRedirect();
        $this->assertDatabaseCount('product_names', 0);
    }
}
