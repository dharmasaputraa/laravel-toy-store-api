<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'LEGO',
                'description' => 'Danish toy production company known for its plastic interlocking building bricks',
                'is_active' => true,
            ],
            [
                'name' => 'Mattel',
                'description' => 'American multinational toy manufacturing company, owner of Barbie, Hot Wheels, and Fisher-Price',
                'is_active' => true,
            ],
            [
                'name' => 'Hasbro',
                'description' => 'American multinational toy and board game company, owner of Monopoly, Nerf, and Transformers',
                'is_active' => true,
            ],
            [
                'name' => 'Bandai',
                'description' => 'Japanese toy and video game company, known for Gundam model kits and Tamagotchi',
                'is_active' => true,
            ],
            [
                'name' => 'Playmobil',
                'description' => 'German toy company famous for its plastic play sets and figures',
                'is_active' => true,
            ],
            [
                'name' => 'Fisher-Price',
                'description' => 'American company that produces educational toys for infants and toddlers',
                'is_active' => true,
            ],
            [
                'name' => 'Hot Wheels',
                'description' => 'Brand of die-cast toy cars introduced by American toymaker Mattel',
                'is_active' => true,
            ],
            [
                'name' => 'Barbie',
                'description' => 'Fashion doll manufactured by American toy company Mattel',
                'is_active' => true,
            ],
            [
                'name' => 'Nerf',
                'description' => 'Toy brand created by Parker Brothers and currently owned by Hasbro',
                'is_active' => true,
            ],
            [
                'name' => 'Mega Bloks',
                'description' => 'Canadian children\'s toy company currently owned by Mattel, producing building blocks',
                'is_active' => true,
            ],
            [
                'name' => 'Takara Tomy',
                'description' => 'Japanese toy and entertainment company, creator of Beyblade and Tomica',
                'is_active' => true,
            ],
            [
                'name' => 'Schleich',
                'description' => 'German toy company famous for its realistic animal figurines',
                'is_active' => true,
            ],
            [
                'name' => 'Ravensburger',
                'description' => 'German toy and game company known for puzzles and board games',
                'is_active' => true,
            ],
            [
                'name' => 'Melissa & Doug',
                'description' => 'American toy company specializing in wooden toys and puzzles',
                'is_active' => true,
            ],
            [
                'name' => 'VTech',
                'description' => 'Hong Kong-based electronic learning products manufacturer',
                'is_active' => true,
            ],
            [
                'name' => 'Play-Doh',
                'description' => 'Modeling compound used by young children for arts and crafts projects',
                'is_active' => true,
            ],
            [
                'name' => 'Crayola',
                'description' => 'American company best known for its crayons and other art supplies',
                'is_active' => true,
            ],
            [
                'name' => 'Nintendo',
                'description' => 'Japanese multinational video game company, producer of gaming consoles and toys',
                'is_active' => true,
            ],
            [
                'name' => 'Disney',
                'description' => 'American entertainment company with extensive toy and merchandise lines',
                'is_active' => true,
            ],
            [
                'name' => 'Marvel',
                'description' => 'American entertainment company known for comic books and superhero merchandise',
                'is_active' => true,
            ],
        ];

        foreach ($brands as $brandData) {
            Brand::create([
                'name' => $brandData['name'],
                'slug' => Str::slug($brandData['name']),
                'description' => $brandData['description'],
                'is_active' => $brandData['is_active'] ?? true,
            ]);

            // $this->command->info("Created brand: {$brandData['name']}");
        }

        // $this->command->info('Brand seeding completed!');
    }
}
