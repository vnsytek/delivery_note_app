<?php

namespace Tests\Feature;

use App\Contracts\ErpOrderSource;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function fakeErp(array $orders): void
    {
        $this->app->bind(ErpOrderSource::class, fn () => new class($orders) implements ErpOrderSource
        {
            public function __construct(private array $orders) {}

            public function chunks(string $since, int $size): iterable
            {
                yield from array_chunk($this->orders, $size);
            }
        });
    }

    /** Một đơn đúng "dạng ERP": chuỗi có khoảng trắng đệm, số dạng chuỗi, ngày YYYYMMDD. */
    private function erpOrder(array $override = []): array
    {
        return array_merge([
            'type' => 'V221', 'no' => '261107003', 'date' => '20261107', 'customer_code' => 'UDE',
            'customer_po' => 'PO-77   ', 'tax_type' => '1', 'tax_rate' => '.0800',
            'amount' => '67222222.00', 'tax' => '5377778.00', 'confirm_code' => 'Y',
            'lines' => [
                ['seq' => '0001', 'item_code' => 'CPU1', 'name' => 'Core i7', 'spec' => '14700', 'quantity' => '5.000000',
                    'delivered_quantity' => '2.000000', 'unit' => 'CAI ', 'unit_price' => '12100000.00000000',
                    'amount' => '60500000.00', 'due_date' => '20261110', 'customer_item_code' => 'VR-1', 'closed_code' => 'N'],
                ['seq' => '0002', 'item_code' => 'RAM8', 'name' => 'RAM 8G', 'spec' => null, 'quantity' => '11.000000',
                    'delivered_quantity' => '.000000', 'unit' => 'CAI ', 'unit_price' => '1100000.00000000',
                    'amount' => '12100000.00', 'due_date' => '        ', 'customer_item_code' => null, 'closed_code' => 'N'],
            ],
        ], $override);
    }

    public function test_sync_copies_orders_and_skips_bad_dates_and_unknown_customers(): void
    {
        Customer::factory()->create(['ma001' => 'UDE']);
        $this->fakeErp([
            $this->erpOrder(),
            $this->erpOrder(['no' => '261107004', 'date' => '20261332']), // ngày không tồn tại
            $this->erpOrder(['no' => '261107005', 'customer_code' => 'XXX']),
        ]);

        $this->artisan('orders:sync-erp')
            ->expectsOutputToContain('Đã đồng bộ 1 đơn')
            ->expectsOutputToContain('Bỏ qua 1 đơn có số đơn/ngày không hợp lệ')
            ->expectsOutputToContain('Bỏ qua 1 đơn vì khách hàng chưa có')
            ->assertSuccessful();

        $order = Order::sole();
        $this->assertSame('V221-261107003', $order->number());
        $this->assertSame('PO-77', $order->customer_po);
        $this->assertTrue($order->pricesIncludeTax());
        $this->assertCount(2, $order->lines);
        $this->assertSame('2026-11-10', $order->lines[0]->due_on->toDateString());
        $this->assertNull($order->lines[1]->due_on);

        // ERP bỏ dòng 2: đồng bộ lại thì bản sao cũng bỏ.
        $changed = $this->erpOrder();
        $changed['lines'] = [$changed['lines'][0]];
        $this->fakeErp([$changed]);
        $this->artisan('orders:sync-erp')->assertSuccessful();
        $this->assertSame(1, OrderLine::count());
    }

    public function test_sync_reports_source_failure(): void
    {
        $this->app->bind(ErpOrderSource::class, fn () => new class implements ErpOrderSource
        {
            public function chunks(string $since, int $size): iterable
            {
                throw new RuntimeException('không kết nối được ERP');
                yield [];
            }
        });

        $this->artisan('orders:sync-erp')->expectsOutputToContain('không kết nối được ERP')->assertFailed();
    }

    public function test_pages_require_login(): void
    {
        $order = Order::factory()->create();
        $this->get('/orders')->assertRedirect('/login');
        $this->get("/orders/{$order->id}")->assertRedirect('/login');
    }

    public function test_list_finds_orders_by_number_customer_or_po(): void
    {
        $this->actingAs(User::factory()->create());
        Customer::factory()->create(['ma001' => 'UDE', 'ma003' => 'C?NG TY UDE']);
        Order::factory()->create(['erp_no' => '261107003', 'customer_code' => 'UDE', 'customer_po' => 'PO-77']);
        Order::factory()->create(['erp_no' => '261001001', 'ordered_on' => '2026-10-01']);

        $this->get('/orders')->assertOk()->assertSee('V221-261107003')->assertSee('V221-261001001')->assertSee('CÔNG TY UDE');
        $this->get('/orders?q=V221-2611')->assertSee('V221-261107003')->assertDontSee('V221-261001001');
        $this->get('/orders?q=PO-77')->assertSee('V221-261107003')->assertDontSee('V221-261001001');
        $this->get('/orders?from=2026-11-01')->assertSee('V221-261107003')->assertDontSee('V221-261001001');
    }

    public function test_order_page_hides_prices_by_default_and_lists_its_delivery_notes(): void
    {
        $this->actingAs(User::factory()->create());
        $order = Order::factory()->create(['erp_no' => '261107003', 'tax_type' => '1', 'amount' => 67222222, 'tax' => 5377778]);
        OrderLine::factory()->create(['order_id' => $order->id, 'seq' => '0001', 'erp_name' => 'Core i7',
            'quantity' => 5, 'delivered_quantity' => 2, 'unit_price' => 12100000, 'amount' => 60500000]);
        $note = DeliveryNote::factory()->create(['erp_type' => 'V231', 'erp_no' => '261107010', 'customer_code' => $order->customer_code]);
        DeliveryNoteLine::factory()->create(['delivery_note_id' => $note->id, 'seq' => '0001',
            'order_type' => 'V221', 'order_no' => '261107003', 'order_seq' => '0001']);
        DeliveryNote::factory()->create(['erp_type' => 'V231', 'erp_no' => '261107011']); // phiếu của đơn khác

        $page = $this->get("/orders/{$order->id}")->assertOk()
            ->assertSee('Xác nhận đơn đặt hàng')
            ->assertSee('Core i7')
            ->assertSee('72.600.000')          // tổng có trong trang, chỉ ẩn bằng CSS
            ->assertSee('đã gồm thuế GTGT')
            ->assertSee('Hiện giá')
            ->assertSee('Đã giao 2/5')
            ->assertSee('V231-261107010')->assertDontSee('V231-261107011');
        $this->assertStringContainsString('class="order-card" id="order-card"', $page->getContent());

        $this->get("/orders/{$order->id}?gia=1")->assertSee('order-card show-prices', false)->assertSee('Ẩn giá');

        // Từ phiếu giao mở ngược lại đơn gốc.
        $this->get("/delivery-notes/{$note->id}")->assertSee('Lập từ đơn')->assertSee(route('orders.show', $order->id));
    }
}
