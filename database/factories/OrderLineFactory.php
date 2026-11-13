<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderLine> */
class OrderLineFactory extends Factory
{
    protected $model = OrderLine::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'seq' => '0001',
            'item_code' => strtoupper($this->faker->unique()->bothify('ITEM-###')),
            'erp_name' => 'Hàng hóa',
            'quantity' => 5,
            'delivered_quantity' => 0,
            'unit_raw' => 'cái',
            'unit_price' => 200000,
            'amount' => 1000000,
            'due_on' => '2026-11-10',
            'closed_code' => 'N',
        ];
    }
}
