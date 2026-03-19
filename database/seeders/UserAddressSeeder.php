<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = User::where('email', 'user@example.com')->first();

        if ($customer) {
            // Alamat Utama
            UserAddress::create([
                'user_id'        => $customer->id,
                'label'          => 'Rumah',
                'recipient_name' => $customer->name,
                'phone'          => $customer->phone,
                'province_id'    => '31', // DKI Jakarta
                'city_id'        => '31.73', // Kota Administrasi Jakarta Barat
                'district'       => 'Kebon Jeruk',
                'postal_code'    => '11530',
                'full_address'   => 'Jl. Panjang No. 123, RT 01 RW 02',
                'is_default'     => true,
            ]);

            // Alamat Tambahan
            UserAddress::create([
                'user_id'        => $customer->id,
                'label'          => 'Kantor',
                'recipient_name' => 'Daren (Receptionist)',
                'phone'          => '021555666',
                'province_id'    => '31', // DKI Jakarta
                'city_id'        => '31.71', // Kota Administrasi Jakarta Pusat
                'district'       => 'Gambir',
                'postal_code'    => '10110',
                'full_address'   => 'Gedung Wisma Merdeka Lt. 5, Jl. Merdeka Barat',
                'is_default'     => false,
            ]);
        }
    }
}
