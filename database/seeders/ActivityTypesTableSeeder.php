<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activityTypes = [
            ['name' => 'NON SAP Dealer Visit', 'status' => '1'],
            ['name' => 'Prospective Dealer Visit', 'status' => '1'],
            ['name' => 'ASK Expert Meetings', 'status' => '1'],
            ['name' => 'Consumer Meetings', 'status' => '1'],
            ['name' => 'MITR Meetings', 'status' => '1'],
            ['name' => 'ACE one to one meetings', 'status' => '1'],
            ['name' => 'Gift and voucher distribution', 'status' => '1'],
        ];

        DB::table('activity_types')->insert($activityTypes);
    }
}
