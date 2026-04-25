<?php

namespace Database\Seeders;

use App\Models\DrugCategory;
use Illuminate\Database\Seeder;

class DrugCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Cardiology'],
            ['name' => 'Neurology'],
            ['name' => 'Oncology'],
            ['name' => 'Pediatrics'],
            ['name' => 'Dermatology'],
        ];

        foreach ($categories as $row) {
            DrugCategory::query()->updateOrCreate(
                ['name' => $row['name']],
                []
            );
        }
    }
}
