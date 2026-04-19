<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DrugCategory;
use App\Models\MedicalRep;
use Illuminate\Database\Seeder;

class MedicalRepSeeder extends Seeder
{
    /**
     * Medical reps table status enum: pending, active, blocked. Use "active" for approved demo accounts.
     */
    public function run(): void
    {
        $pharma = Company::query()->where('email', 'company@pharmaegypt.com')->firstOrFail();
        $cardiology = DrugCategory::query()->where('name', 'Cardiology')->firstOrFail();
        $neurology = DrugCategory::query()->where('name', 'Neurology')->firstOrFail();

        MedicalRep::query()->updateOrCreate(
            ['email' => 'rep1@erep.com'],
            [
                'full_name' => 'Karim Mostafa',
                'password' => 'password123',
                'company_id' => $pharma->id,
                'category_id' => $cardiology->id,
                'phone' => '+201200000001',
                'national_id' => '29002002002001',
                'status' => 'active',
            ]
        );

        MedicalRep::query()->updateOrCreate(
            ['email' => 'rep2@erep.com'],
            [
                'full_name' => 'Nada Samir',
                'password' => 'password123',
                'company_id' => $pharma->id,
                'category_id' => $neurology->id,
                'phone' => '+201200000002',
                'national_id' => '29002002002002',
                'status' => 'active',
            ]
        );
    }
}
