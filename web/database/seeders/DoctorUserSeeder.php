<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class DoctorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctor = User::factory()->doctor('123456', 'SP')->create([
            'name'         => 'Dr. João Santos',
            'email'        => 'doctor@example.com',
            'cpf'          => '98765432100',
            'phone_number' => '11912345678',
        ]);

        Address::create([
            'user_id'       => $doctor->id,
            'location_name' => 'Consultório',
            'full_address'  => 'Av. Paulista, 1000 - Bela Vista, São Paulo - SP',
            'complement'    => 'Sala 1501',
            'cep'           => '01310100',
        ]);

        Address::create([
            'user_id'       => $doctor->id,
            'location_name' => 'Clínica',
            'full_address'  => 'Rua Augusta, 500 - Consolação, São Paulo - SP',
            'complement'    => null,
            'cep'           => '01304000',
        ]);
    }
}
