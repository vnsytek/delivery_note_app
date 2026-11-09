<?php

namespace App\Console\Commands;

use App\Services\OrderSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncErpOrders extends Command
{
    protected $signature = 'orders:sync-erp {--since= : Chỉ lấy đơn từ ngày này (YYYYMMDD)}';

    protected $description = 'Đồng bộ đơn đặt hàng (COPTC/COPTD) từ ERP vào MySQL. Chỉ đọc ERP; không xóa đơn. Chạy customers:sync-erp trước.';

    public function handle(OrderSyncService $sync): int
    {
        $since = $this->option('since') ?: config('erp.delivery_note.since');

        try {
            $result = $sync->sync($since);
        } catch (Throwable $e) {
            $this->error('Đồng bộ thất bại: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Đã đồng bộ {$result['synced']} đơn đặt hàng (từ {$since}).");
        if ($result['invalid']) {
            $this->warn("Bỏ qua {$result['invalid']} đơn có số đơn/ngày không hợp lệ.");
        }
        if ($result['unknown_customer']) {
            $this->warn("Bỏ qua {$result['unknown_customer']} đơn vì khách hàng chưa có (chạy customers:sync-erp).");
        }

        return self::SUCCESS;
    }
}
