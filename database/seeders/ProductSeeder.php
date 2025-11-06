<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productId = DB::table('products')->insertGetId([
            'product_name' => 'Tata Tiscon',
        ]);

        $productDetails = [
            ['product_id' => $productId, 'type_id' => 1, 'rate' => 1000.00],
            ['product_id' => $productId, 'type_id' => 2, 'rate' => 200.00],
            ['product_id' => $productId, 'type_id' => 3, 'rate' => 20.00],
            ['product_id' => $productId, 'type_id' => 4, 'rate' => 15.00],
        ];

        DB::table('products_details')->insert($productDetails);
    }
}
