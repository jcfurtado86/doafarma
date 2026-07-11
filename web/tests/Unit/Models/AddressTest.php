<?php

declare(strict_types = 1);

use App\Models\Address;

it('builds the formatted address from the granular fields', function (): void {
    $address = Address::factory()->make([
        'street'       => 'Av. Paulista',
        'number'       => '1000',
        'neighborhood' => 'Bela Vista',
        'city'         => 'São Paulo',
        'uf'           => 'SP',
    ]);

    expect($address->formatted_address)
        ->toBe('Av. Paulista, 1000 - Bela Vista, São Paulo - SP');
});
