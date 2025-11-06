<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Dealer;

class DealersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Dealer::create([
            'dealer_code' => 'D001',
            'phone' => '1234567890',
            'email' => 'dealer1@example.com',
            'address' => '123 Main St, City, State',
            'user_zone' => 'Zone 1',
            'pincode' => '123456',
            'state' => 'State Name',
            'district' => 'District Name',
            'taluk' => 'Taluk Name',
        ]);

        Dealer::create([
            'dealer_code' => 'D002',
            'phone' => '0987654321',
            'email' => 'dealer2@example.com',
            'address' => '456 Another St, City, State',
            'user_zone' => 'Zone 2',
            'pincode' => '654321',
            'state' => 'State Name',
            'district' => 'District Name',
            'taluk' => 'Taluk Name',
        ]);
    }
}
