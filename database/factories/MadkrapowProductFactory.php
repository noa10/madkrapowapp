<?php

namespace Database\Factories;

use App\Models\MadkrapowProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class MadkrapowProductFactory extends Factory
{
    protected $model = MadkrapowProduct::class;

    public function definition()
    {
        return [
            'product_name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
