<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;

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

it('is the default address for its owner when it matches the user default_address_id', function (): void {
    $user    = User::factory()->make(['id' => 10, 'default_address_id' => 7]);
    $address = Address::factory()->make(['id' => 7, 'user_id' => 10]);

    expect($address->isDefaultFor($user))->toBeTrue();
});

it('is not the default address when the owner has a different default', function (): void {
    $user    = User::factory()->make(['id' => 10, 'default_address_id' => 99]);
    $address = Address::factory()->make(['id' => 7, 'user_id' => 10]);

    expect($address->isDefaultFor($user))->toBeFalse();
});

it('is not the default address for a null user', function (): void {
    $address = Address::factory()->make(['id' => 7, 'user_id' => 10]);

    expect($address->isDefaultFor(null))->toBeFalse();
});

it('is not the default address for a viewer who does not own it, even when their default_address_id points to it', function (): void {
    $viewer  = User::factory()->make(['id' => 20, 'default_address_id' => 7]);
    $address = Address::factory()->make(['id' => 7, 'user_id' => 10]);

    expect($address->isDefaultFor($viewer))->toBeFalse();
});
