<?php

namespace Database\Seeders;

use App\Models\ProductTag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            [
                'name' => 'New Arrival',
                'slug' => 'new-arrival',
            ],
            [
                'name' => 'Best Seller',
                'slug' => 'best-seller',
            ],
            [
                'name' => 'Limited Edition',
                'slug' => 'limited-edition',
            ],
            [
                'name' => 'Popular',
                'slug' => 'popular',
            ],
            [
                'name' => 'On Sale',
                'slug' => 'on-sale',
            ],
            [
                'name' => 'Trending',
                'slug' => 'trending',
            ],
            [
                'name' => 'Exclusive',
                'slug' => 'exclusive',
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
            ],
            [
                'name' => 'Budget Friendly',
                'slug' => 'budget-friendly',
            ],
            [
                'name' => 'Gift Idea',
                'slug' => 'gift-idea',
            ],
            [
                'name' => 'Educational',
                'slug' => 'educational',
            ],
            [
                'name' => 'Collectible',
                'slug' => 'collectible',
            ],
        ];

        foreach ($tags as $tag) {
            ProductTag::create([
                'name' => $tag['name'],
                'slug' => $tag['slug'],
            ]);

            // $this->command->info("Created product tag: {$tag['name']}");
        }

        // $this->command->info('Product tag seeding completed!');
    }
}
