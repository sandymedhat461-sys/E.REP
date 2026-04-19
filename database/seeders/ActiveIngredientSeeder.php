<?php

namespace Database\Seeders;

use App\Models\ActiveIngredient;
use Illuminate\Database\Seeder;

class ActiveIngredientSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Paracetamol',
            'Ibuprofen',
            'Amoxicillin',
            'Omeprazole',
            'Metformin',
            'Atorvastatin',
            'Amlodipine',
            'Lisinopril',
            'Aspirin',
            'Cetirizine',
        ];

        foreach ($names as $name) {
            ActiveIngredient::query()->updateOrCreate(
                ['name' => $name],
                ['created_by_company_id' => null]
            );
        }
    }
}
