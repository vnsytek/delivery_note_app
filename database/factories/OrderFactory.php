<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'erp_type' => 'V221',
            'erp_no' => $this->faker->unique()->numerify('2611#####'),
            'ordered_on' => '2026-11-06',
            'customer_code' => fn () => Customer::factory()->create()->ma001,
            'customer_po' => 'PO-'.$this->faker->numerify('####'),
            'tax_type' => '2',
            'tax_rate' => 0.08,
            'amount' => 1000000,
            'tax' => 80000,
            'confirm_code' => 'Y',
        ];
    }
}
