<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Chẩn đoán đồng bộ ERP trên một máy (nhất là host mới): driver nào đang dùng, có kết nối được ERP không, ERP có bao nhiêu
 * khách / phiếu / đơn so với MySQL của app, và lần đồng bộ gần nhất cách đây bao lâu. Chỉ chạy SELECT COUNT, không ghi gì.
 */
class CheckErpConnection extends Command
{
    protected $signature = 'erp:check';

    protected $description = 'Kiểm tra kết nối ERP và so số khách hàng / phiếu / đơn giữa ERP và MySQL của app (chỉ đọc).';

    /** Mã khách của dữ liệu demo (DemoCustomerSeeder, DemoDeliveryNoteSeeder): không có trong ERP thật. */
    private const DEMO_CUSTOMERS = ['A001', 'A002', 'A003', 'A004', 'A005', 'A006', 'A007', 'A008'];

    public function handle(): int
    {
        $driver = config('database.connections.erp.driver');
        $this->line("Driver kết nối ERP: <info>{$driver}</info> (php ".PHP_VERSION.', '.PHP_OS_FAMILY.')');
        foreach (['pdo_sqlsrv', 'pdo_odbc'] as $extension) {
            $this->line("  extension {$extension}: ".(extension_loaded($extension) ? '<info>có</info>' : '<comment>không</comment>'));
        }

        $this->newLine();
        $this->line('Trong MySQL của app:');
        $local = [
            'khách hàng' => Customer::count(),
            'phiếu giao hàng' => DeliveryNote::count(),
            'đơn đặt hàng' => Order::count(),
        ];
        foreach ($local as $label => $count) {
            $this->line("  {$label}: <info>{$count}</info>");
        }
        $this->reportDemoData();
        $this->reportLastSync();

        $this->newLine();
        try {
            $erp = [
                'khách hàng' => DB::connection('erp')->selectOne('select count(*) as n from COPMA')->n,
                'phiếu giao hàng' => DB::connection('erp')->selectOne('select count(*) as n from COPTG')->n,
                'đơn đặt hàng' => DB::connection('erp')->selectOne('select count(*) as n from COPTC')->n,
            ];
        } catch (Throwable $e) {
            $this->error('KHÔNG kết nối được ERP: '.$e->getMessage());
            $this->line('Kiểm tra: máy này có tới được '.config('database.connections.erp.host').':'.config('database.connections.erp.port')
                .' không (cùng mạng / VPN / tường lửa), extension PHP ở trên, và ERP_DB_* trong .env.');

            return self::FAILURE;
        }

        $this->line('Trong ERP (bảng gốc, chưa lọc loại chứng từ):');
        foreach ($erp as $label => $count) {
            $this->line("  {$label}: <info>{$count}</info>");
        }
        $this->info('Kết nối ERP hoạt động. Nếu số liệu MySQL còn ít, chạy: php artisan customers:sync-erp rồi orders:sync-erp --since=20180101 và delivery-notes:sync-erp --since=20190101.');

        return self::SUCCESS;
    }

    private function reportDemoData(): void
    {
        $demo = Customer::whereIn('ma001', self::DEMO_CUSTOMERS)->count();
        if ($demo > 0) {
            $this->warn("  Có {$demo} khách DEMO (A001–A008) - dữ liệu mẫu, không phải từ ERP; đồng bộ ERP thành công thì có thể xóa chúng.");
        }
    }

    private function reportLastSync(): void
    {
        $last = Customer::max('synced_at');
        if ($last === null) {
            $this->warn('  Chưa có lần đồng bộ ERP nào (không khách nào có thời điểm đồng bộ).');

            return;
        }
        $at = Carbon::parse($last);
        $minutes = (int) $at->diffInMinutes(now());
        $this->line('  Đồng bộ khách hàng gần nhất: '.$at->setTimezone(config('delivery_note.timezone'))->format('d/m/Y H:i')." ({$minutes} phút trước)");
        if ($minutes > 15) {
            $this->warn('  Hơn 15 phút chưa có đồng bộ mới: lịch tự động (cron `php artisan schedule:run` mỗi phút) có thể chưa chạy.');
        }
    }
}
