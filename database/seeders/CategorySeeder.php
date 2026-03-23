<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Action Figures & Collectibles
            [
                'name' => 'Action Figures',
                'description' => 'Action figures and collectible toys from popular franchises',
                'sort_order' => 1,
                'is_active' => true,
                'children' => [
                    ['name' => 'Superheroes', 'description' => 'Superhero action figures', 'sort_order' => 1],
                    ['name' => 'Anime Figures', 'description' => 'Anime and manga collectibles', 'sort_order' => 2],
                    ['name' => 'Movie Figures', 'description' => 'Movie-themed action figures', 'sort_order' => 3],
                    ['name' => 'Sports Figures', 'description' => 'Sports player collectibles', 'sort_order' => 4],
                ],
            ],
            // Building Blocks
            [
                'name' => 'Building Blocks',
                'description' => 'Construction toys and building sets',
                'sort_order' => 2,
                'is_active' => true,
                'children' => [
                    ['name' => 'LEGO Sets', 'description' => 'Official LEGO building sets', 'sort_order' => 1],
                    ['name' => 'Block Bricks', 'description' => 'Generic building blocks', 'sort_order' => 2],
                    ['name' => 'Magnetic Building', 'description' => 'Magnetic construction toys', 'sort_order' => 3],
                ],
            ],
            // Dolls & Plushies
            [
                'name' => 'Dolls & Plushies',
                'description' => 'Dolls, plush toys, and stuffed animals',
                'sort_order' => 3,
                'is_active' => true,
                'children' => [
                    ['name' => 'Fashion Dolls', 'description' => 'Fashion and style dolls', 'sort_order' => 1],
                    ['name' => 'Baby Dolls', 'description' => 'Baby dolls and accessories', 'sort_order' => 2],
                    ['name' => 'Plush Toys', 'description' => 'Soft stuffed animals and characters', 'sort_order' => 3],
                    ['name' => 'Character Plushies', 'description' => 'Licensed character plush toys', 'sort_order' => 4],
                ],
            ],
            // Board Games & Puzzles
            [
                'name' => 'Board Games & Puzzles',
                'description' => 'Board games, card games, and puzzles',
                'sort_order' => 4,
                'is_active' => true,
                'children' => [
                    ['name' => 'Family Games', 'description' => 'Games for all ages', 'sort_order' => 1],
                    ['name' => 'Strategy Games', 'description' => 'Strategic and tactical games', 'sort_order' => 2],
                    ['name' => 'Card Games', 'description' => 'Playing cards and card games', 'sort_order' => 3],
                    ['name' => 'Jigsaw Puzzles', 'description' => 'Puzzle games and brain teasers', 'sort_order' => 4],
                ],
            ],
            // Educational Toys
            [
                'name' => 'Educational Toys',
                'description' => 'Learning and developmental toys',
                'sort_order' => 5,
                'is_active' => true,
                'children' => [
                    ['name' => 'STEM Toys', 'description' => 'Science, Technology, Engineering, Math toys', 'sort_order' => 1],
                    ['name' => 'Language & Reading', 'description' => 'Reading and language learning toys', 'sort_order' => 2],
                    ['name' => 'Math & Counting', 'description' => 'Mathematical learning toys', 'sort_order' => 3],
                    ['name' => 'Art & Creativity', 'description' => 'Creative and artistic toys', 'sort_order' => 4],
                ],
            ],
            // Outdoor Toys
            [
                'name' => 'Outdoor Toys',
                'description' => 'Toys for outdoor play and activities',
                'sort_order' => 6,
                'is_active' => true,
                'children' => [
                    ['name' => 'Sports Equipment', 'description' => 'Sports gear and equipment', 'sort_order' => 1],
                    ['name' => 'Ride-on Toys', 'description' => 'Bikes, scooters, and ride-on toys', 'sort_order' => 2],
                    ['name' => 'Water Toys', 'description' => 'Pool and water play toys', 'sort_order' => 3],
                    ['name' => 'Playground Equipment', 'description' => 'Swings, slides, and playground toys', 'sort_order' => 4],
                ],
            ],
            // Vehicles
            [
                'name' => 'Vehicles',
                'description' => 'Toy cars, trucks, and vehicle playsets',
                'sort_order' => 7,
                'is_active' => true,
                'children' => [
                    ['name' => 'Die-cast Cars', 'description' => 'Collectible die-cast vehicles', 'sort_order' => 1],
                    ['name' => 'Remote Control', 'description' => 'RC cars and vehicles', 'sort_order' => 2],
                    ['name' => 'Trucks & Construction', 'description' => 'Toy trucks and construction vehicles', 'sort_order' => 3],
                    ['name' => 'Train Sets', 'description' => 'Model trains and railway sets', 'sort_order' => 4],
                ],
            ],
            // Baby & Toddler Toys
            [
                'name' => 'Baby & Toddler Toys',
                'description' => 'Safe toys for babies and toddlers',
                'sort_order' => 8,
                'is_active' => true,
                'children' => [
                    ['name' => 'Rattles & Teethers', 'description' => 'Sensory toys for babies', 'sort_order' => 1],
                    ['name' => 'Activity Centers', 'description' => 'Play and learning centers', 'sort_order' => 2],
                    ['name' => 'Musical Toys', 'description' => 'Musical and sound-making toys', 'sort_order' => 3],
                    ['name' => 'Bath Toys', 'description' => 'Water-safe toys for bath time', 'sort_order' => 4],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            // Create parent category
            $category = Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
                'sort_order' => $categoryData['sort_order'],
                'is_active' => $categoryData['is_active'] ?? true,
                'parent_id' => null,
            ]);

            // $this->command->info("Created category: {$category->name}");

            // Create child categories
            foreach ($children as $childData) {
                $child = Category::create([
                    'name' => $childData['name'],
                    'slug' => Str::slug($childData['name']),
                    'description' => $childData['description'] ?? null,
                    'sort_order' => $childData['sort_order'],
                    'is_active' => true,
                    'parent_id' => $category->id,
                ]);

                // $this->command->info("  - Created subcategory: {$child->name}");
            }
        }

        // $this->command->info('Category seeding completed!');
    }
}
