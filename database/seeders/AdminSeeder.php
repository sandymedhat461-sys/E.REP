<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::query()->updateOrCreate(
            ['email' => 'admin@erep.com'],
            [
                'full_name' => 'Super Admin',
                'phone' => '+201000000001',
                'password' => 'password123',
            ]
        );
    }
}
