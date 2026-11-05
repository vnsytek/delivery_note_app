<?php

namespace Database\Seeders;

use App\Models\ProductName;
use Illuminate\Database\Seeder;

/**
 * Nạp tên hàng đúng dấu lấy từ các phiếu giao hàng Excel cũ (data/product_names.csv).
 * Không ghi đè tên đã được người dùng sửa trên phiếu.
 */
class ProductNameSeeder extends Seeder
{
    public function run(): void
    {
        $handle = fopen(__DIR__.'/data/product_names.csv', 'r');
        fgetcsv($handle, null, ',', '"', ''); // dòng tiêu đề
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if (count($row) === 2 && $row[0] !== '') {
                ProductName::firstOrCreate(['item_code' => $row[0]], ['name' => $row[1]]);
            }
        }
        fclose($handle);
    }
}
