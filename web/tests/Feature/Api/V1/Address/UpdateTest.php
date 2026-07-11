<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

it('should be accessible via PUT /api/v1/addresses/{address}', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    putJson("/api/v1/addresses/{$address->id}", ['label' => 'Novo nome'])->assertOk();
});

it('updates only the provided fields and returns 200', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id, 'label' => 'Antigo']);

    actingAs($user, 'sanctum');

    $response = putJson(route('api.v1.addresses.update', $address), ['label' => 'Novo nome']);

    $response->assertOk();
    $response->assertJson(['data' => ['id' => $address->id, 'label' => 'Novo nome']]);

    assertDatabaseHas('addresses', ['id' => $address->id, 'label' => 'Novo nome']);
});

it('keeps is_default true after updating an address that was already the default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    $response = putJson(route('api.v1.addresses.update', $address), ['label' => 'Novo nome']);

    $response->assertOk();
    $response->assertJson(['data' => ['is_default' => true]]);
});

it('returns 403 when updating another user\'s address', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user, 'sanctum');

    putJson(route('api.v1.addresses.update', $otherAddress), ['label' => 'Hack'])
        ->assertForbidden();

    assertDatabaseHas('addresses', ['id' => $otherAddress->id, 'label' => $otherAddress->label]);
});

it('cleans the cep field when provided', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    putJson(route('api.v1.addresses.update', $address), ['cep' => '98765-432'])
        ->assertOk();

    assertDatabaseHas('addresses', ['id' => $address->id, 'cep' => '98765432']);
});

it('returns a validation error for an invalid uf', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    putJson(route('api.v1.addresses.update', $address), ['uf' => 'XX'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['uf']);
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    $address = Address::factory()->create();

    putJson(route('api.v1.addresses.update', $address), ['label' => 'Novo nome'])
        ->assertUnauthorized();
});

it('should return 404 Not Found for a non-existent address', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    putJson('/api/v1/addresses/99999', ['label' => 'Novo nome'])->assertNotFound();
});
