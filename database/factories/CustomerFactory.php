<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'ma001' => strtoupper($this->faker->unique()->bothify('C###??')),
            'ma002' => $this->faker->company(),
            'ma003' => $this->faker->company().' Co., Ltd',
            'extra' => ['MA005' => $this->faker->phoneNumber()],
        ];
    }
}
