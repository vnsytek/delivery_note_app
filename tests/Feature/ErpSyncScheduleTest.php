<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ErpSyncScheduleTest extends TestCase
{
    public function test_customers_then_delivery_notes_are_synced_every_five_minutes(): void
    {
        // Gọi một lệnh artisan để nạp routes/console.php (nơi khai báo lịch).
        $this->artisan('schedule:list')->assertSuccessful();

        $events = collect(app(Schedule::class)->events())->values();
        $position = fn (string $command) => $events->search(fn (Event $e) => str_contains((string) $e->command, $command));

        $customers = $position('customers:sync-erp');
        $orders = $position('orders:sync-erp');
        $notes = $position('delivery-notes:sync-erp');
        $this->assertNotFalse($customers, 'thiếu lịch customers:sync-erp');
        $this->assertNotFalse($orders, 'thiếu lịch orders:sync-erp');
        $this->assertNotFalse($notes, 'thiếu lịch delivery-notes:sync-erp');
        $this->assertLessThan($orders, $customers, 'khách hàng phải đồng bộ trước đơn đặt hàng');
        $this->assertLessThan($notes, $customers, 'khách hàng phải đồng bộ trước phiếu giao hàng');

        foreach ([$customers, $orders, $notes] as $i) {
            $this->assertSame('*/5 * * * *', $events[$i]->expression);
            $this->assertTrue($events[$i]->withoutOverlapping, 'hai lần đồng bộ không được chạy chồng nhau');
        }
    }
}
