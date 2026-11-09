<?php

namespace Database\Seeders;

use App\Models\ProductName;
use Illuminate\Database\Seeder;

/**
 * Nạp tên hàng đúng dấu lấy từ các phiếu giao hàng Excel cũ (data/product_names.csv).
 * Không ghi đè tên đã được người dùng sửa trên phiếu (chỉ bổ sung tên tiếng Trung còn trống).
 */
class ProductNameSeeder extends Seeder
{
    public function run(): void
    {
        $handle = fopen(__DIR__.'/data/product_names.csv', 'r');
        fgetcsv($handle, null, ',', '"', ''); // dòng tiêu đề
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if (count($row) === 3 && $row[0] !== '') {
                $entry = ProductName::firstOrCreate(['item_code' => $row[0]], ['name' => $row[1], 'name_zh' => $row[2] ?: null]);
                if ($entry->name_zh === null && $row[2] !== '') {
                    $entry->update(['name_zh' => $row[2]]); // bổ sung tên Trung, không ghi đè tên người dùng đã sửa
                }
            }
        }
        fclose($handle);
    }
}
