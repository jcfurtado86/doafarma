<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

function validAddressPayload(array $overrides = []): array
{
    return array_merge([
        'label'        => 'Consultório Centro',
        'cep'          => '12345-678',
        'uf'           => 'SP',
        'city'         => 'São Paulo',
        'neighborhood' => 'Centro',
        'street'       => 'Rua A',
        'number'       => '123',
        'complement'   => 'Sala 1',
    ], $overrides);
}

it('should be accessible via POST /api/v1/addresses', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    postJson('/api/v1/addresses', validAddressPayload())->assertCreated();
});

it('creates the address for the authenticated user and returns 201', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    $response = postJson(route('api.v1.addresses.store'), validAddressPayload());

    $response->assertCreated();
    $response->assertJson([
        'data' => [
            'label'             => 'Consultório Centro',
            'uf'                => 'SP',
            'city'              => 'São Paulo',
            'neighborhood'      => 'Centro',
            'street'            => 'Rua A',
            'number'            => '123',
            'complement'        => 'Sala 1',
            'formatted_address' => 'Rua A, 123 - Centro, São Paulo - SP',
        ],
    ]);

    assertDatabaseHas('addresses', [
        'user_id' => $user->id,
        'label'   => 'Consultório Centro',
        'cep'     => '12345678',
    ]);

    assertDatabaseCount('addresses', 1);
});

it('sets the first address created as the user default', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    $response = postJson(route('api.v1.addresses.store'), validAddressPayload());

    $addressId = $response->json('data.id');
    expect($user->fresh()->default_address_id)->toBe($addressId);
    $response->assertJson(['data' => ['is_default' => true]]);
});

it('does not override the default when the user already has one', function (): void {
    $user            = User::factory()->create();
    $existingAddress = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $existingAddress->id]);

    actingAs($user, 'sanctum');

    $response = postJson(route('api.v1.addresses.store'), validAddressPayload());

    $response->assertCreated();
    $response->assertJson(['data' => ['is_default' => false]]);
    expect($user->fresh()->default_address_id)->toBe($existingAddress->id);
});

it('returns a validation error if required fields are missing', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    $response = postJson(route('api.v1.addresses.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['label', 'cep', 'uf', 'city', 'neighborhood', 'street', 'number']);
});

it('returns a validation error for an invalid uf', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    postJson(route('api.v1.addresses.store'), validAddressPayload(['uf' => 'XX']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['uf']);
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    postJson(route('api.v1.addresses.store'), validAddressPayload())->assertUnauthorized();
});
