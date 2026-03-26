<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => fake()->unique()->bothify('PRD-####'),
            'description' => fake()->paragraph(3),
            'short_description' => fake()->sentence(),
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory()->create()->id,
            'brand_id' => Brand::inRandomOrder()->first()?->id ?? Brand::factory()->create()->id,
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'is_featured' => fake()->boolean(20), // 20% chance of being featured
        ];
    }

    /**
     * Indicate that product is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Attach random tags to the product.
     */
    public function withTags(int $count = 2): static
    {
        return $this->afterCreating(function (Product $product) use ($count) {
            $tags = ProductTag::inRandomOrder()->limit($count)->get();
            $product->tags()->attach($tags);
        });
    }

    /**
     * Create product with specific category.
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn(array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Create product with specific brand.
     */
    public function forBrand(Brand $brand): static
    {
        return $this->state(fn(array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }
}
