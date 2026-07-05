<?php
namespace Database\Factories;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1000;
        $counter++;
        return [
            'code'           => 'PRD-'.str_pad($counter,5,'0',STR_PAD_LEFT),
            'name'           => fake()->words(3, true),
            'description'    => fake()->sentence(),
            'category_id'    => Category::inRandomOrder()->first()?->id ?? 1,
            'stock'          => fake()->numberBetween(0,200),
            'min_stock'      => 10,
            'purchase_price' => fake()->numberBetween(1000,500000),
            'sale_price'     => fake()->numberBetween(2000,600000),
            'unit'           => fake()->randomElement(['pcs','kg','liter','box','karton']),
            'is_active'      => true,
        ];
    }
}
