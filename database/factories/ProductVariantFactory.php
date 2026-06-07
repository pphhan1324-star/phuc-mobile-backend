<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(Str::random(12)),
            'price' => $this->faker->randomFloat(2, 500000, 40000000),
            'stock_quantity' => $this->faker->numberBetween(1, 100),
            'color' => $this->faker->colorName(),
            'size' => $this->faker->randomElement(['Small', 'Medium', 'Large', 'Extra Large']),
            'is_available' => true,
        ];
    }
}
