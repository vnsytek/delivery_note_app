<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckErpConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_local_counts_and_demo_data_even_when_erp_is_unreachable(): void
    {
        config(['database.connections.erp' => ['driver' => 'erp_odbc', 'host' => '', 'database' => '', 'username' => '', 'prefix' => '']]);
        Customer::factory()->create(['ma001' => 'A001', 'synced_at' => now()->subHour()]);
        Customer::factory()->create(['ma001' => 'HDV', 'synced_at' => now()->subHour()]);

        $this->artisan('erp:check')
            ->expectsOutputToContain('khách hàng: 2')
            ->expectsOutputToContain('1 khách DEMO')
            ->expectsOutputToContain('chưa chạy')
            ->expectsOutputToContain('KHÔNG kết nối được ERP')
            ->assertFailed();
    }
}
