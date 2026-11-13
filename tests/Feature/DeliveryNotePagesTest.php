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

    public function test_quantity_can_be_changed_for_printing_without_changing_the_erp_quantity(): void
    {
        $this->login();
        $note = $this->note(['erp_no' => '261113001'], 1, ['quantity' => 1234, 'unit_raw' => 'cái']);
        $line = $note->lines()->first();

        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['quantity' => ' 37 ']]])->assertRedirect();
        $line->refresh();
        $this->assertSame('37.000', $line->quantity_manual);
        $this->assertSame('1234.000', $line->quantity, 'số lượng ERP giữ nguyên');

        // Bản in dùng số sửa tay; trang phiếu báo đã sửa và hiện số ERP.
        $this->get("/delivery-notes/{$note->id}/print")->assertOk()->assertSee('37')->assertDontSee('1234');
        $this->get("/delivery-notes/{$note->id}")->assertSee('value="37"', false)->assertSee('đã sửa')->assertSee('ERP: 1234');

        // Dấu phẩy thập phân; lưu phần khác (không gửi số lượng) thì giữ số đã sửa.
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['quantity' => '1,5']]]);
        $this->assertSame('1.500', $line->fresh()->quantity_manual);
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['remark' => 'x']]]);
        $this->assertSame('1.500', $line->fresh()->quantity_manual);

        // Nhập đúng số ERP hoặc để trống = quay về theo ERP.
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['quantity' => '1234']]]);
        $this->assertNull($line->fresh()->quantity_manual);
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['quantity' => '9']]]);
        $this->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['quantity' => '']]]);
        $this->assertNull($line->fresh()->quantity_manual);
        $this->get("/delivery-notes/{$note->id}")->assertDontSee('đã sửa');

        foreach (['abc', '-1', '1.2345', '1,2,3'] as $bad) {
            $this->from("/delivery-notes/{$note->id}")
                ->put("/delivery-notes/{$note->id}", ['lines' => [$line->id => ['quantity' => $bad]]])
                ->assertSessionHasErrors('lines.'.$line->id.'.quantity');
        }
        $this->assertNull($line->fresh()->quantity_manual);
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
            ->assertSeeInOrder(['STT', 'MÃ LIỆU SANG Y', 'Quy cách', 'SL', 'ĐVT', 'PO No.', 'Mã hàng UDE', 'Mã bộ phận'])
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
}
