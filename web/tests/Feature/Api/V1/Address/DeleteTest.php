<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\MedicationAppointment;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;

it('returns 422 and keeps the address when it is referenced by a medication appointment', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    MedicationAppointment::factory()->completed()->create(['address_id' => $address->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertUnprocessable();

    // 422 comes from DeleteAddressAction catching the FK violation, not from a pre-check
    assertDatabaseHas('addresses', ['id' => $address->id]);
});

it('should be accessible via DELETE /api/v1/addresses/{address}', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    deleteJson("/api/v1/addresses/{$address->id}")->assertNoContent();
});

it('deletes the address and returns 204 for the owner', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertNoContent();

    assertDatabaseMissing('addresses', ['id' => $address->id]);
});

it('clears default_address_id when the deleted address was the default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertNoContent();

    expect($user->fresh()->default_address_id)->toBeNull();
});

it('does not auto-promote another address to default when the deleted default address had siblings', function (): void {
    $user           = User::factory()->create();
    $defaultAddress = Address::factory()->create(['user_id' => $user->id]);
    $otherAddress   = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $defaultAddress->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $defaultAddress))->assertNoContent();

    expect($user->fresh()->default_address_id)->toBeNull();
    expect($user->fresh()->addresses)->toHaveCount(1);
    expect(Address::find($otherAddress->id))->not->toBeNull();
});

it('allows deleting the last remaining address', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertNoContent();

    expect($user->fresh()->addresses)->toHaveCount(0);
});

it('returns 403 when deleting another user\'s address', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $otherAddress))->assertForbidden();

    expect(Address::find($otherAddress->id))->not->toBeNull();
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    $address = Address::factory()->create();

    deleteJson(route('api.v1.addresses.delete', $address))->assertUnauthorized();
});

it('should return 404 Not Found for a non-existent address', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    deleteJson('/api/v1/addresses/99999')->assertNotFound();
});
