<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DeliveryNote;
use Illuminate\Database\Seeder;

/** Một phiếu mẫu dựng theo ảnh phiếu thật (khách UDE) để thử giao diện và bản in. Chỉ dùng cho dev. */
class DemoDeliveryNoteSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(app()->isProduction(), 403, 'Không nạp dữ liệu demo trên production.');

        Customer::updateOrCreate(['ma001' => 'UDE'], [
            'ma002' => 'UDE 創源',
            'ma003' => 'CÔNG TY TNHH U.D.ELECTRONIC VIỆT NAM',
            'synced_at' => now(),
        ]);

        $note = DeliveryNote::updateOrCreate(['erp_type' => 'V231', 'erp_no' => '261028005'], [
            'issued_on' => '2026-11-03',
            'customer_code' => 'UDE',
            'erp_customer_name' => 'CÔNG TY TNHH U.D.ELECTRONIC VIỆT NAM',
            'receiver' => 'Ms. Hien - 0344536330',
            'supplier_code' => 'NL0088',
            'synced_at' => now(),
        ]);

        $po = '33V2-261026009';
        $lines = [
            ['001', 'MR24E1N1100D', 'Man hinh (LCD) PHILIPS', '23.8INCH/1920x1080/120Hz/IPS/HDMI/DVI/VGA/ĐEN', 24, 'set', 'VR-205140001'],
            ['002', 'CPUI7-14700', 'Core i7-14700', null, 3, 'set', 'VR-308110009'],
            ['003', 'CPUG6900', 'Intel Celeron G6900', null, 10, 'set', 'VR-308110011'],
            ['004', 'CPU7-265B', 'Intel, Core Ultra 7 265', null, 10, 'set', 'VR-308110016'],
            ['005', 'CPU5-225B', 'Intel, Core Ultra 5 225', null, 10, 'set', 'VR-308110023'],
        ];
        foreach ($lines as [$seq, $code, $name, $spec, $qty, $unit, $customerItem]) {
            $note->lines()->updateOrCreate(['seq' => $seq], [
                'item_code' => $code, 'erp_name' => $name, 'erp_spec' => $spec, 'quantity' => $qty,
                'unit_raw' => $unit, 'customer_po' => $po, 'customer_item_code' => $customerItem,
                'dept_code' => 'MVP30',
            ]);
        }
    }
}
