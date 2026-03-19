<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionTestSeeder extends Seeder
{
    /**
     * Run the database seeds with minimal data for testing.
     */
    public function run(): void
    {
        $regions = [
            // Provinces
            ['code' => '11', 'name' => 'Aceh', 'level' => 1, 'parent_code' => null],
            ['code' => '31', 'name' => 'DKI Jakarta', 'level' => 1, 'parent_code' => null],

            // Cities for Aceh
            ['code' => '11.01', 'name' => 'Kab. Aceh Besar', 'level' => 2, 'parent_code' => '11'],
            ['code' => '11.02', 'name' => 'Kab. Aceh Selatan', 'level' => 2, 'parent_code' => '11'],

            // Cities for DKI Jakarta
            ['code' => '31.71', 'name' => 'Kota Administrasi Jakarta Pusat', 'level' => 2, 'parent_code' => '31'],
            ['code' => '31.73', 'name' => 'Kota Administrasi Jakarta Barat', 'level' => 2, 'parent_code' => '31'],
        ];

        DB::table('regions')->insert($regions);
    }
}
