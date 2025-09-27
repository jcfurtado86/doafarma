<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DoctorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->doctor()->create([
            'name'  => 'Doctor User',
            'email' => 'doctor@example.com',
        ]);
    }
}
