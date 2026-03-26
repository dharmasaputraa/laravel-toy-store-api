<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active products
        $products = Product::active()->get();

        // Create 1-3 variants for each product
        foreach ($products as $product) {
            $variantCount = rand(1, 3);

            ProductVariant::factory()
                ->count($variantCount)
                ->forProduct($product)
                ->create();
        }

        // Create some inactive variants
        ProductVariant::factory()
            ->count(5)
            ->inactive()
            ->create();

        // Create some out of stock variants
        ProductVariant::factory()
            ->count(10)
            ->outOfStock()
            ->create();

        // Create some variants with discount
        ProductVariant::factory()
            ->count(8)
            ->withDiscount()
            ->create();

        // $this->command->info('Product variant seeding completed!');
    }
}
