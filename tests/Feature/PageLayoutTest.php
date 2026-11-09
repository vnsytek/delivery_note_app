<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->travelTo(Carbon::parse('2026-11-06 09:00:00', 'Asia/Ho_Chi_Minh'));
    }

    public function test_note_list_groups_by_day_and_counts_the_whole_day_across_pages(): void
    {
        Customer::factory()->create(['ma001' => 'UDE']);
        DeliveryNote::factory()->count(27)->create(['issued_on' => '2026-11-06', 'customer_code' => 'UDE']);
        DeliveryNote::factory()->create(['issued_on' => '2026-11-05', 'customer_code' => 'UDE']);

        $this->get('/delivery-notes')->assertOk()
            ->assertSee('Hôm nay có 27 phiếu.')
            ->assertSee('Hôm nay, thứ Sáu 06/11/2026')
            ->assertSee('27 phiếu');                       // cả ngày, dù trang 1 chỉ hiện 25

        $this->get('/delivery-notes?page=2')->assertOk()
            ->assertSee('Hôm nay, thứ Sáu 06/11/2026')
            ->assertSee('Hôm qua, thứ Năm 05/11/2026');
    }

    public function test_note_page_shows_the_print_preview_and_warns_about_unrestored_names(): void
    {
        $note = DeliveryNote::factory()->create(['erp_type' => 'V231', 'erp_no' => '261106001']);
        DeliveryNoteLine::factory()->create(['delivery_note_id' => $note->id, 'seq' => '0001', 'erp_name' => 'Q?X ZZ?']);

        $this->get("/delivery-notes/{$note->id}")->assertOk()
            ->assertSee('1 tên hàng còn ký tự')
            ->assertSee('<mark class="lost">?</mark>', false)
            ->assertSee(route('delivery-notes.print', [$note->id, 'template' => 'excel', 'preview' => 1]))
            ->assertSee('Mẫu ERP');

        // ?template là mảng (URL sửa tay) không làm lỗi trang.
        $this->get("/delivery-notes/{$note->id}?template[]=erp")->assertOk();
    }

    public function test_print_preview_has_no_toolbar(): void
    {
        $note = DeliveryNote::factory()->create();
        DeliveryNoteLine::factory()->create(['delivery_note_id' => $note->id, 'seq' => '0001']);

        $this->get("/delivery-notes/{$note->id}/print?preview=1")->assertOk()
            ->assertSee('is-preview', false)->assertDontSee('print-button', false);
        $this->get("/delivery-notes/{$note->id}/print")->assertOk()
            ->assertSee('print-button', false)->assertSee('Mẫu Excel');
    }

    public function test_customer_page_counts_names_to_check_and_opens_the_review_tab_from_the_url(): void
    {
        Customer::factory()->create(['ma001' => 'A1', 'ma002' => 'A', 'ma003' => 'Q?X LTD']);
        Customer::factory()->create(['ma001' => 'B1', 'ma002' => 'B', 'ma003' => 'CLEAN NAME']);

        $this->get('/')->assertOk()
            ->assertSee('2 khách hàng từ ERP.')
            ->assertSee('1 tên còn ký tự')
            ->assertSee('data-review="0"', false);

        $this->get('/?review=1')->assertOk()->assertSee('data-review="1"', false);
    }
}
