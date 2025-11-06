<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('activities')->insert([
            [
                'activity_type_id' => 1,
                'dealer_id' => 1,
                'employee_id' => 1,
                'assigned_date' => '2025-01-23',
                'due_date' => '2025-01-30',
                'instructions' => 'Follow up with the dealer for pending tasks.',
                'status' => 'Pending',
                'record_details' => null,
                'attachments' => null,
                'completed_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
