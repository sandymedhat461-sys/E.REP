<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Doctors table status enum: pending, active, blocked. Use "active" for approved demo accounts.
     */
    public function run(): void
    {
        $doctors = [
            [
                'email' => 'doctor1@erep.com',
                'full_name' => 'Dr. Ahmed Sayed',
                'phone' => '+201100000001',
                'national_id' => '29001001001001',
                'specialization' => 'Cardiology',
                'syndicate_id' => 'SYN001',
            ],
            [
                'email' => 'doctor2@erep.com',
                'full_name' => 'Dr. Sara Hassan',
                'phone' => '+201100000002',
                'national_id' => '29001001001002',
                'specialization' => 'Neurology',
                'syndicate_id' => 'SYN002',
            ],
            [
                'email' => 'doctor3@erep.com',
                'full_name' => 'Dr. Mohamed Ali',
                'phone' => '+201100000003',
                'national_id' => '29001001001003',
                'specialization' => 'Oncology',
                'syndicate_id' => 'SYN003',
            ],
        ];

        foreach ($doctors as $row) {
            Doctor::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'full_name' => $row['full_name'],
                    'phone' => $row['phone'],
                    'national_id' => $row['national_id'],
                    'password' => 'password123',
                    'specialization' => $row['specialization'],
                    'syndicate_id' => $row['syndicate_id'],
                    'status' => 'active',
                ]
            );
        }
    }
}
