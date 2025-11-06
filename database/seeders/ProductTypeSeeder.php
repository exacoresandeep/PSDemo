<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductType;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $types = ['6mm','8mm','10mm','12mm','16mm','20mm','25mm','32mm'];

        foreach ($types as $type) {
            ProductType::create(['type_name' => $type]);
        }
    }
}
