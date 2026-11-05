<?php

namespace App\Console\Commands;

use App\Services\CustomerSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncErpCustomers extends Command
{
    protected $signature = 'customers:sync-erp';

    protected $description = 'Đồng bộ khách hàng (dbo.COPMA) từ ERP SQL Server vào MySQL. Chỉ đọc ERP; không xóa dữ liệu.';

    public function handle(CustomerSyncService $sync): int
    {
        try {
            $count = $sync->sync();
        } catch (Throwable $e) {
            $this->error('Đồng bộ thất bại: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Đã đồng bộ {$count} khách hàng.");

        return self::SUCCESS;
    }
}
