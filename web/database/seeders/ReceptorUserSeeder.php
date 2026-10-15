<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Actions\Address\StoreAddressAction;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReceptorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param StoreAddressAction $storeAddress creates the addresses so the first one becomes the default
     */
    public function run(StoreAddressAction $storeAddress): void
    {
        $receptor = User::factory()->receptor()->create([
            'name'         => 'Maria Silva',
            'email'        => 'receptor@example.com',
            'cpf'          => '12345678900',
            'phone_number' => '11987654321',
        ]);

        $storeAddress->execute($receptor, [
            'label'        => 'Casa',
            'street'       => 'Rua das Flores',
            'number'       => '123',
            'neighborhood' => 'Centro',
            'city'         => 'São Paulo',
            'uf'           => 'SP',
            'complement'   => 'Apartamento 45',
            'cep'          => '01310100',
        ]);
    }
}
