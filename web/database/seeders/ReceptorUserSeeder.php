<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReceptorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $receptor = User::factory()->receptor()->create([
            'name'         => 'Maria Silva',
            'email'        => 'receptor@example.com',
            'cpf'          => '12345678900',
            'phone_number' => '11987654321',
        ]);

        Address::create([
            'user_id'       => $receptor->id,
            'location_name' => 'Casa',
            'full_address'  => 'Rua das Flores, 123 - Centro, São Paulo - SP',
            'complement'    => 'Apartamento 45',
            'cep'           => '01310100',
        ]);
    }
}
