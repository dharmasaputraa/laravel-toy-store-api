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
        // Predefined tags
        $tags = [
            'New Arrival',
            'Best Seller',
            'Limited Edition',
            'Popular',
            'On Sale',
            'Trending',
            'Exclusive',
            'Premium',
            'Budget Friendly',
            'Gift Idea',
            'Educational',
            'Collectible',
        ];

        foreach ($tags as $tagName) {
            ProductTag::factory()->withName($tagName)->create();

            // $this->command->info("Created product tag: {$tagName}");
        }

        // Optionally add some random tags
        // ProductTag::factory()->count(5)->create();

        // $this->command->info('Product tag seeding completed!');
    }
}
