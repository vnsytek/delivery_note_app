<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\DeliveryNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliveryNote> */
class DeliveryNoteFactory extends Factory
{
    protected $model = DeliveryNote::class;

    public function definition(): array
    {
        return [
            'erp_type' => 'V231',
            'erp_no' => $this->faker->unique()->numerify('26####'.'###'),
            'issued_on' => '2026-03-20',
            'customer_code' => fn () => Customer::factory()->create()->ma001,
            'erp_customer_name' => $this->faker->company(),
            'erp_address' => $this->faker->address(),
        ];
    }
}
