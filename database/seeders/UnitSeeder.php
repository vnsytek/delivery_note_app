<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

/** Danh mục đơn vị tính chuẩn + các cách ghi thường gặp trong ERP/phiếu cũ (kể cả dạng mất dấu '?'). */
class UnitSeeder extends Seeder
{
    private const UNITS = [
        'cái' => ['cái', 'chiếc', '个', '个 / CÁI', 'cai', 'chiec', 'c?i', 'chi?c', 'pcs', 'pc'],
        'cuộn' => ['cuộn', 'cuon', 'cu?n', '卷'],
        'bộ' => ['bộ', 'bo', 'b?', '套'],
        'bình' => ['bình', 'binh', 'b?nh'],
        'hộp' => ['hộp', 'hop', 'h?p'],
        'sợi' => ['sợi', 'soi', 's?i'],
        'gói' => ['gói', 'goi', 'g?i'],
        'set' => ['set'],
    ];

    public function run(): void
    {
        foreach (self::UNITS as $name => $aliases) {
            $unit = Unit::firstOrCreate(['name' => $name]);
            foreach (array_unique([$name, ...$aliases]) as $alias) {
                $unit->aliases()->firstOrCreate(['alias' => $alias]);
            }
        }
    }
}
