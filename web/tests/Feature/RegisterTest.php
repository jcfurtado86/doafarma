<?php

declare(strict_types = 1);

use App\Models\User;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertTrue;

it('should be able to register', function (): void {
    postJson(route('register'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'phone_number'          => '(96) 98765-4321', // DDD + 9XXX-XXXX
        'crm'                   => '123456',
        'crm_uf'                => 'SP', // UF do CRM
        'password'              => 'password',
        'password_confirmation' => 'password',
        'addresses'             => [
            [
                'location_name' => 'Clínica X',
                'full_address'  => 'Rua A, 123, Bairro B, Cidade C, Estado D',
                'complement'    => 'Sala 1',
                'cep'           => '12345-678',
            ],
        ],
        'terms_accepted' => true,
    ])->assertCreated();

    assertDatabaseHas('users', [
        'name'              => 'John Doe',
        'email'             => 'test@example.com',
        'phone_number'      => '96987654321',
        'crm'               => '123456',
        'crm_uf'            => 'SP',
        'terms_accepted'    => 1,
        'terms_accepted_at' => now(),
    ]);

    assertTrue(password_verify('password', (string) User::whereEmail('test@example.com')->first()->password));

    assertDatabaseHas('addresses', [
        'location_name' => 'Clínica X',
        'full_address'  => 'Rua A, 123, Bairro B, Cidade C, Estado D',
        'complement'    => 'Sala 1',
        'cep'           => '12345678',
    ]);

    assertDatabaseCount('users', 1);
    assertDatabaseCount('addresses', 1);
});

it('should ensure that there is a relationship between the user and their addresses', function (): void {
    postJson(route('register'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'phone_number'          => '(96) 98765-4321',
        'crm'                   => '123456',
        'crm_uf'                => 'SP',
        'password'              => 'password',
        'password_confirmation' => 'password',
        'addresses'             => [
            [
                'location_name' => 'Clínica X',
                'full_address'  => 'Rua A, 123, Bairro B, Cidade C, Estado D',
                'complement'    => 'Sala 1',
                'cep'           => '12345-678',
            ],
        ],
        'terms_accepted' => true,
    ])->assertCreated();

    $user    = User::whereEmail('test@example.com')->first();
    $address = $user->addresses()->first();

    expect($address)
        ->not->toBeNull()
        ->and($address->user_id)->toBe($user->id)
        ->and($address->location_name)->toBe('Clínica X');
});
