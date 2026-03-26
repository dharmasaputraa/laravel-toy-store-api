<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 20 products with tags
        Product::factory()
            ->count(20)
            ->withTags(rand(1, 3))
            ->create();

        // Create 5 featured products
        Product::factory()
            ->count(5)
            ->featured()
            ->withTags(rand(2, 4))
            ->create();

        // Create 10 inactive products
        Product::factory()
            ->count(10)
            ->inactive()
            ->withTags(rand(1, 2))
            ->create();

        // $this->command->info('Product seeding completed!');
    }
}
