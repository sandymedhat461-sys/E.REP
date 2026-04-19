<?php

namespace Database\Seeders;

use App\Models\DrugCategory;
use Illuminate\Database\Seeder;

class DrugCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Cardiology', 'line_manager_name' => 'Dr. Ahmed Hassan'],
            ['name' => 'Neurology', 'line_manager_name' => 'Dr. Sara Ali'],
            ['name' => 'Oncology', 'line_manager_name' => 'Dr. Mohamed Kamal'],
            ['name' => 'Pediatrics', 'line_manager_name' => 'Dr. Nour Ibrahim'],
            ['name' => 'Dermatology', 'line_manager_name' => 'Dr. Layla Mostafa'],
        ];

        foreach ($categories as $row) {
            DrugCategory::query()->updateOrCreate(
                ['name' => $row['name']],
                ['line_manager_name' => $row['line_manager_name']]
            );
        }
    }
}
