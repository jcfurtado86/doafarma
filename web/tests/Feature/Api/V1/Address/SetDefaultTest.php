<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;

it('should be accessible via PATCH /api/v1/addresses/{address}/default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    patchJson("/api/v1/addresses/{$address->id}/default")->assertOk();
});

it('sets the address as the user default and returns is_default true', function (): void {
    $user         = User::factory()->create();
    $firstAddress = Address::factory()->create(['user_id' => $user->id]);
    $newDefault   = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $firstAddress->id]);

    actingAs($user, 'sanctum');

    $response = patchJson(route('api.v1.addresses.set-default', $newDefault));

    $response->assertOk();
    $response->assertJson(['data' => ['id' => $newDefault->id, 'is_default' => true]]);

    expect($user->fresh()->default_address_id)->toBe($newDefault->id);
});

it('is idempotent when the address is already the default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    patchJson(route('api.v1.addresses.set-default', $address))->assertOk();

    expect($user->fresh()->default_address_id)->toBe($address->id);
});

it('returns 403 when setting another user\'s address as default', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user, 'sanctum');

    patchJson(route('api.v1.addresses.set-default', $otherAddress))->assertForbidden();

    expect($otherUser->fresh()->default_address_id)->not->toBe($otherAddress->id);
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    $address = Address::factory()->create();

    patchJson(route('api.v1.addresses.set-default', $address))->assertUnauthorized();
});

it('should return 404 Not Found for a non-existent address', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    patchJson('/api/v1/addresses/99999/default')->assertNotFound();
});
