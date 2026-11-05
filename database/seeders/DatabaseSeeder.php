<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Dữ liệu danh mục cần cho mọi môi trường. Dữ liệu demo nạp riêng bằng --class=Demo*Seeder. */
    public function run(): void
    {
        $this->call([UnitSeeder::class, ProductNameSeeder::class]);
    }
}
