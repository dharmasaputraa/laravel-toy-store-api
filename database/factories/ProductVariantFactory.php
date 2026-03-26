<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->numberBetween(50000, 500000); // 50k - 500k IDR

        $attributes = fake()->randomElement([
            ['color' => fake()->colorName()],
            ['size' => fake()->randomElement(['S', 'M', 'L', 'XL', 'XXL'])],
            ['color' => fake()->colorName(), 'size' => fake()->randomElement(['S', 'M', 'L'])],
            ['material' => fake()->word()],
            ['color' => fake()->colorName(), 'size' => fake()->randomElement(['S', 'M', 'L']), 'material' => fake()->word()],
        ]);

        return [
            'product_id' => Product::inRandomOrder()->first()?->id,
            'sku' => $this->generateSkuForFactory($attributes),
            'name' => fake()->word(),
            'price' => $price,
            'compare_price' => fake()->optional(30)->numberBetween($price * 1.1, $price * 1.5),
            'stock' => fake()->numberBetween(0, 100),
            'attributes' => $attributes,
            'is_active' => fake()->boolean(85), // 85% chance of being active
        ];
    }

    /**
     * Indicate that variant is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that variant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create variant with specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn(array $attributes) => [
            'product_id' => $product->id,
        ]);
    }

    /**
     * Create variant in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock' => fake()->numberBetween(1, 100),
        ]);
    }

    /**
     * Create variant out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock' => 0,
        ]);
    }

    /**
     * Create variant with specific price range.
     */
    public function withPrice(int $min, int $max): static
    {
        return $this->state(fn(array $attributes) => [
            'price' => fake()->numberBetween($min, $max),
        ]);
    }

    /**
     * Create variant with discount.
     */
    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'] ?? fake()->numberBetween(50000, 500000);
            return [
                'compare_price' => fake()->numberBetween($price * 1.1, $price * 1.5),
            ];
        });
    }

    /**
     * Generate SKU for factory use.
     */
    protected function generateSkuForFactory(array $attributes): string
    {
        $attributesPart = collect($attributes)
            ->values()
            ->map(fn($v) => strtoupper(Str::slug($v)))
            ->join('-');

        $random = strtoupper(Str::random(4));

        return trim("VARIANT-{$attributesPart}-{$random}", '-');
    }
}
