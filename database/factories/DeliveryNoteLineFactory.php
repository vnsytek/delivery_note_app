<?php

namespace Database\Factories;

use App\Models\DeliveryNote;
use App\Models\DeliveryNoteLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliveryNoteLine> */
class DeliveryNoteLineFactory extends Factory
{
    protected $model = DeliveryNoteLine::class;

    public function definition(): array
    {
        return [
            'delivery_note_id' => DeliveryNote::factory(),
            'seq' => '001',
            'item_code' => strtoupper($this->faker->unique()->bothify('ITEM-###')),
            'erp_name' => 'Hàng hóa',
            'quantity' => 1,
            'unit_raw' => 'cái',
        ];
    }
}
