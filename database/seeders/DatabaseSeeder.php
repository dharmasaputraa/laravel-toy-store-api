<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RegionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            UserAddressSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            ProductTagSeeder::class,
            ProductSeeder::class,
            ProductVariantSeeder::class,
        ]);
    }
}
