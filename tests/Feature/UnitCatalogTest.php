<?php

namespace Tests\Feature;

use App\Models\ProductName;
use App\Services\UnitCatalog;
use Database\Seeders\ProductNameSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_variants_resolve_to_the_standard_unit(): void
    {
        $this->seed(UnitSeeder::class);
        $catalog = new UnitCatalog;

        foreach (['cái', 'CÁI', 'chiếc', 'CHIẾC', '个', '个 / CÁI', 'chi?c'] as $raw) {
            $this->assertSame(['name' => 'cái', 'known' => true], $catalog->resolve($raw), $raw);
        }
        $this->assertSame('cuộn', $catalog->resolve('CUỘN')['name']);
        $this->assertSame('cuộn', $catalog->resolve('cu?n')['name']);
        $this->assertSame('set', $catalog->resolve('Set')['name']);
    }

    public function test_unknown_and_empty_units(): void
    {
        $this->seed(UnitSeeder::class);
        $catalog = new UnitCatalog;

        $this->assertSame(['name' => 'thùng', 'known' => false], $catalog->resolve('thùng'));
        $this->assertSame(['name' => '', 'known' => true], $catalog->resolve(null));
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seed(UnitSeeder::class);
        $this->seed(UnitSeeder::class);
        $this->seed(ProductNameSeeder::class);
        $this->seed(ProductNameSeeder::class);

        $this->assertDatabaseCount('units', 8);
        $this->assertDatabaseHas('product_names', ['item_code' => 'LOGIK120', 'name' => 'Bàn phím Logitech K120']);
        $this->assertSame(14, ProductName::count());
    }
}
