<?php

namespace App\Console\Commands;

use App\Services\DeliveryNoteSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncErpDeliveryNotes extends Command
{
    protected $signature = 'delivery-notes:sync-erp {--since= : Chỉ lấy phiếu từ ngày này (YYYYMMDD)}';

    protected $description = 'Đồng bộ phiếu bán hàng (COPTG/COPTH) từ ERP vào MySQL. Chỉ đọc ERP; không xóa dữ liệu. Chạy customers:sync-erp trước.';

    public function handle(DeliveryNoteSyncService $sync): int
    {
        $since = $this->option('since') ?: config('erp.delivery_note.since');

        try {
            $result = $sync->sync($since);
        } catch (Throwable $e) {
            $this->error('Đồng bộ thất bại: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Đã đồng bộ {$result['synced']} phiếu (từ {$since}).");
        if ($result['invalid_number']) {
            $this->warn("Bỏ qua {$result['invalid_number']} phiếu có số phiếu/ngày không hợp lệ.");
        }
        if ($result['unknown_customer']) {
            $this->warn("Bỏ qua {$result['unknown_customer']} phiếu vì khách hàng chưa có (chạy customers:sync-erp).");
        }

        return self::SUCCESS;
    }
}
