<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3);
        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => strtoupper(Str::random(10)),
            'description' => $this->faker->paragraph(),
            'material' => $this->faker->word(),
            'brand' => $this->faker->company(),
            'base_price' => $this->faker->randomFloat(2, 1000000, 50000000),
            'is_active' => true,
            'is_featured' => false,
        ];
    }
}
