<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự đồng bộ từ ERP (chỉ đọc) 5 phút/lần, chạy lần lượt: khách hàng trước (đơn và phiếu tham chiếu tới khách),
// rồi đơn đặt hàng, rồi phiếu giao hàng. Lịch chạy khi có `php artisan schedule:work` (đã gồm trong
// `composer run dev`) hoặc Task Scheduler gọi `php artisan schedule:run` mỗi phút - xem README, mục "Tự đồng bộ".
Schedule::command('customers:sync-erp')->everyFiveMinutes()->withoutOverlapping(15);
Schedule::command('orders:sync-erp')->everyFiveMinutes()->withoutOverlapping(15);
Schedule::command('delivery-notes:sync-erp')->everyFiveMinutes()->withoutOverlapping(15);

// Đoán lại dấu cho các từ ERP bị mất dấu (có từ mới sau mỗi lần đồng bộ). Chỉ ghi vào bảng accent_words của app.
Schedule::command('accent:suggest')->hourly()->withoutOverlapping(30);
