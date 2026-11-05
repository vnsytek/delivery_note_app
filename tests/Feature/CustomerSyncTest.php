<?php

namespace Tests\Feature;

use App\Contracts\ErpCustomerSource;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSyncTest extends TestCase
{
    use RefreshDatabase;

    private function fakeErp(array $rows): void
    {
        $this->app->bind(ErpCustomerSource::class, fn () => new class($rows) implements ErpCustomerSource
        {
            public function __construct(private array $rows) {}

            public function chunks(int $size): iterable
            {
                yield from array_chunk($this->rows, $size);
            }
        });
    }

    public function test_sync_inserts_and_updates_without_touching_manual_names_or_deleting(): void
    {
        Customer::factory()->create(['ma001' => 'A001', 'ma002' => 'cũ', 'ma003' => 'cũ', 'ma003_manual' => 'Bản sửa tay']);
        Customer::factory()->create(['ma001' => 'GONE']); // không còn trong ERP nhưng không được xóa

        $this->fakeErp([
            ['MA001' => 'A001   ', 'MA002' => 'MỚI  ', 'MA003' => 'C?NG TY', 'MA005' => ' 0900 ', 'MA006' => '', 'MA007' => null],
            ['MA001' => 'A002', 'MA002' => 'B', 'MA003' => 'C'],
            ['MA001' => '   ', 'MA002' => 'bỏ qua dòng không có mã'],
        ]);

        $this->artisan('customers:sync-erp')->expectsOutput('Đã đồng bộ 2 khách hàng.')->assertSuccessful();

        $a001 = Customer::where('ma001', 'A001')->first();
        $this->assertSame('MỚI', $a001->ma002);
        $this->assertSame('Bản sửa tay', $a001->ma003_manual);
        $this->assertSame(['MA005' => '0900'], $a001->extra);
        $this->assertNotNull($a001->synced_at);
        $this->assertSame(3, Customer::count());
        $this->assertTrue(Customer::where('ma001', 'GONE')->exists());
    }

    public function test_sync_reports_failure(): void
    {
        $this->app->bind(ErpCustomerSource::class, fn () => new class implements ErpCustomerSource
        {
            public function chunks(int $size): iterable
            {
                throw new \RuntimeException('không kết nối được');
                yield [];
            }
        });

        $this->artisan('customers:sync-erp')->expectsOutputToContain('không kết nối được')->assertFailed();
    }
}
