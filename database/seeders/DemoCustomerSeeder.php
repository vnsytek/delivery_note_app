<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

/** Dữ liệu mẫu (tên mất dấu giống ERP thật) để chạy thử giao diện. Chỉ dùng cho môi trường dev. */
class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(app()->isProduction(), 403, 'Không nạp dữ liệu demo trên production.');

        $rows = [
            ['A001', 'PHÚ L?M', 'C?NG TY TNHH PHÚ L?M'],
            ['A002', 'H?I D??NG', 'C?NG TY C? PH?N S?N XU?T H?I D??NG'],
            ['A003', 'DAI HIEP', 'C?NG TY TNHH ?ÀI HI?P'],
            ['A004', 'CT TM', 'C?NG TY TH??NG M?I VI?T NAM'],
            ['A005', 'KT', 'C?NG TY TNHH K? THU?T NGUY?N ?'],
            ['A006', 'TAN XUONG', 'Công ty TNHH Tân Xương'],
            ['A007', 'IN AN', 'c?ng ty in ?n d?ch v?'],
            ['A008', 'GIAY', 'C?NG TY GI?Y VÀ NH?A MI?N B?C'],
        ];

        foreach ($rows as [$code, $short, $full]) {
            Customer::updateOrCreate(
                ['ma001' => $code],
                ['ma002' => $short, 'ma003' => $full, 'extra' => ['MA005' => '0900 000 '.substr($code, 1)], 'synced_at' => now()],
            );
        }
    }
}
