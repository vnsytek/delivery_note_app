<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $this->actingAs(User::factory()->create());
    }

    public function test_guests_cannot_use_api_or_page(): void
    {
        $this->getJson('/api/customers')->assertUnauthorized();
        $this->putJson('/api/overrides/A001', ['MA002' => 'x'])->assertUnauthorized();
        $this->get('/')->assertRedirect('/login');
    }

    public function test_page_renders_for_logged_in_user(): void
    {
        $this->login();
        $this->get('/')->assertOk()->assertSee('customers-app', false)->assertSee('csrf-token', false);
    }

    public function test_list_restores_accents_and_reports_status(): void
    {
        $this->login();
        Customer::factory()->create(['ma001' => 'A001', 'ma002' => 'PHU LAM', 'ma003' => 'C?NG TY PHÚ L?M']);

        $this->getJson('/api/customers')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.MA001', 'A001')
            ->assertJsonPath('items.0.MA003', 'C?NG TY PHÚ L?M')
            ->assertJsonPath('items.0.MA003_VI', 'CÔNG TY PHÚ LÂM')
            ->assertJsonPath('items.0.status.MA002', 'ok')
            ->assertJsonPath('items.0.status.MA003', 'auto');
    }

    public function test_search_ignores_accents_and_case(): void
    {
        $this->login();
        Customer::factory()->create(['ma001' => 'A001', 'ma002' => 'X', 'ma003' => 'C?NG TY PHÚ L?M']);
        Customer::factory()->create(['ma001' => 'A002', 'ma002' => 'Y', 'ma003' => 'Khác hẳn']);

        $this->getJson('/api/customers?q=cong ty phu lam')->assertJsonPath('total', 1)->assertJsonPath('items.0.MA001', 'A001');
        $this->getJson('/api/customers?q=CÔNG')->assertJsonPath('total', 1);
        $this->getJson('/api/customers?q=a002')->assertJsonPath('items.0.MA001', 'A002');
    }

    public function test_review_filter_keeps_only_unconfirmed_rows(): void
    {
        $this->login();
        Customer::factory()->create(['ma001' => 'A001', 'ma002' => 'ok', 'ma003' => 'Đầy đủ dấu']);
        Customer::factory()->create(['ma001' => 'A002', 'ma002' => 'ok', 'ma003' => 'C?NG TY']);
        Customer::factory()->create(['ma001' => 'A003', 'ma002' => 'ok', 'ma003' => 'C?NG TY', 'ma003_manual' => 'CÔNG TY']);

        $this->getJson('/api/customers?review=1')->assertJsonPath('total', 1)->assertJsonPath('items.0.MA001', 'A002');
    }

    public function test_pagination(): void
    {
        $this->login();
        foreach (['A1', 'A2', 'A3'] as $code) {
            Customer::factory()->create(['ma001' => $code]);
        }

        $this->getJson('/api/customers?limit=2&offset=2')
            ->assertJsonPath('total', 3)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.MA001', 'A3');
    }

    public function test_list_validates_query_parameters(): void
    {
        $this->login();
        $this->getJson('/api/customers?limit=0')->assertUnprocessable();
        $this->getJson('/api/customers?limit=501')->assertUnprocessable();
        $this->getJson('/api/customers?offset=-1')->assertUnprocessable();
        $this->getJson('/api/customers?q='.str_repeat('a', 101))->assertUnprocessable();
    }

    public function test_show_returns_all_columns_and_404_for_unknown(): void
    {
        $this->login();
        Customer::factory()->create(['ma001' => 'A001', 'ma002' => 'S', 'ma003' => 'L', 'extra' => ['MA005' => '0900']]);

        $this->getJson('/api/customers/A001')->assertOk()->assertJsonPath('MA005', '0900')->assertJsonPath('MA001', 'A001');
        $this->getJson('/api/customers/NOPE')->assertNotFound();
    }

    public function test_update_names_saves_and_clears_manual_values(): void
    {
        $this->login();
        $customer = Customer::factory()->create(['ma001' => 'A001', 'ma002' => 'S', 'ma003' => 'C?NG TY X?']);

        $this->putJson('/api/overrides/A001', ['MA002' => '  Tên mới ', 'MA003' => 'CÔNG TY XỲ'])->assertOk()->assertJson(['ok' => true]);
        $customer->refresh();
        $this->assertSame('Tên mới', $customer->ma002_manual);
        $this->assertSame('CÔNG TY XỲ', $customer->ma003_manual);
        $this->getJson('/api/customers/A001')->assertJsonPath('status.MA003', 'manual');

        // Chuỗi rỗng xóa bản sửa; trường không gửi thì giữ nguyên.
        $this->putJson('/api/overrides/A001', ['MA002' => ''])->assertOk();
        $customer->refresh();
        $this->assertNull($customer->ma002_manual);
        $this->assertSame('CÔNG TY XỲ', $customer->ma003_manual);
        // Dữ liệu gốc từ ERP không bị đổi.
        $this->assertSame('C?NG TY X?', $customer->ma003);
    }

    public function test_update_names_validates_input_and_404(): void
    {
        $this->login();
        Customer::factory()->create(['ma001' => 'A001']);

        $this->putJson('/api/overrides/A001', ['MA002' => str_repeat('a', 256)])->assertUnprocessable();
        $this->putJson('/api/overrides/A001', ['MA003' => ['array']])->assertUnprocessable();
        $this->putJson('/api/overrides/NOPE', ['MA002' => 'x'])->assertNotFound();
    }
}
