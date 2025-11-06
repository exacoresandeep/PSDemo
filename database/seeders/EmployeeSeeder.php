<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Employee::create([
            'employee_code' => 'EMP001',
            'name' => 'John Doe',
            'designation' => 'Software Engineer',
            'email' => 'johndoe@example.com',
            'phone' => '1234567890',
            'employee_type_id' => 1, 
            'password' => Hash::make('password123'),
            'address' => '123 Main Street, City, Country',
            'photo' => null, 
            'emergency_contact' => '9876543210',
        ]);

        Employee::create([
            'employee_code' => 'EMP002',
            'name' => 'Jane Smith',
            'designation' => 'Project Manager',
            'email' => 'janesmith@example.com',
            'phone' => '9876543210',
            'employee_type_id' => 2,  
            'password' => Hash::make('password123'),
            'address' => '456 Another Street, City, Country',
            'photo' => null, 
            'emergency_contact' => '1234567890',
        ]);
    }
}
