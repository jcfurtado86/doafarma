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
    $user    = User::factory()->receptor()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    MedicationAppointment::factory()->completed()->create(['address_id' => $address->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertUnprocessable();

    // 422 comes from DeleteAddressAction catching the FK violation, not from a pre-check
    assertDatabaseHas('addresses', ['id' => $address->id]);
});

it('should be accessible via DELETE /api/v1/addresses/{address}', function (): void {
    $user    = User::factory()->receptor()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    deleteJson("/api/v1/addresses/{$address->id}")->assertNoContent();
});

it('deletes the address and returns 204 for the owner', function (): void {
    $user    = User::factory()->receptor()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertNoContent();

    assertDatabaseMissing('addresses', ['id' => $address->id]);
});

it('clears default_address_id when the deleted address was the default', function (): void {
    $user    = User::factory()->receptor()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertNoContent();

    expect($user->fresh()->default_address_id)->toBeNull();
});

it('does not auto-promote another address to default when the deleted default address had siblings', function (): void {
    $user           = User::factory()->receptor()->create();
    $defaultAddress = Address::factory()->create(['user_id' => $user->id]);
    $otherAddress   = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $defaultAddress->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $defaultAddress))->assertNoContent();

    expect($user->fresh()->default_address_id)->toBeNull();
    expect($user->fresh()->addresses)->toHaveCount(1);
    expect(Address::find($otherAddress->id))->not->toBeNull();
});

// Rule 8
it('allows a receptor to delete their last remaining address', function (): void {
    $user    = User::factory()->receptor()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertNoContent();

    expect($user->fresh()->addresses)->toHaveCount(0);
});

// Rule 6
it('returns 422 and keeps the address when a doctor deletes their only address', function (): void {
    $doctor  = User::factory()->doctor()->create();
    $address = Address::factory()->create(['user_id' => $doctor->id]);

    actingAs($doctor, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Você precisa manter pelo menos um endereço cadastrado.']);

    assertDatabaseHas('addresses', ['id' => $address->id]);
});

// Rule 6
it('returns 422 when a doctor deletes their only address even if it is the default', function (): void {
    $doctor  = User::factory()->doctor()->create();
    $address = Address::factory()->create(['user_id' => $doctor->id]);
    $doctor->update(['default_address_id' => $address->id]);

    actingAs($doctor, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Você precisa manter pelo menos um endereço cadastrado.']);

    assertDatabaseHas('addresses', ['id' => $address->id]);
    expect($doctor->fresh()->default_address_id)->toBe($address->id);
});

// Rule 7
it('allows a doctor with two or more addresses to delete one of them', function (): void {
    $doctor        = User::factory()->doctor()->create();
    $address       = Address::factory()->create(['user_id' => $doctor->id]);
    $secondAddress = Address::factory()->create(['user_id' => $doctor->id]);
    $thirdAddress  = Address::factory()->create(['user_id' => $doctor->id]);

    actingAs($doctor, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertNoContent();

    assertDatabaseMissing('addresses', ['id' => $address->id]);
    assertDatabaseHas('addresses', ['id' => $secondAddress->id]);
    assertDatabaseHas('addresses', ['id' => $thirdAddress->id]);
});

// Rule 9
it('returns 422 when a doctor with several addresses deletes one linked to an appointment', function (): void {
    $doctor       = User::factory()->doctor()->create();
    $address      = Address::factory()->create(['user_id' => $doctor->id]);
    $otherAddress = Address::factory()->create(['user_id' => $doctor->id]);
    MedicationAppointment::factory()->create(['address_id' => $address->id]);

    actingAs($doctor, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Este endereço está vinculado a um ou mais agendamentos e não pode ser removido.']);

    assertDatabaseHas('addresses', ['id' => $address->id]);
    assertDatabaseHas('addresses', ['id' => $otherAddress->id]);
});

// Rule 10
it('allows a doctor to delete the default address without promoting another one', function (): void {
    $doctor         = User::factory()->doctor()->create();
    $defaultAddress = Address::factory()->create(['user_id' => $doctor->id]);
    $otherAddress   = Address::factory()->create(['user_id' => $doctor->id]);
    $doctor->update(['default_address_id' => $defaultAddress->id]);

    actingAs($doctor, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $defaultAddress))->assertNoContent();

    assertDatabaseMissing('addresses', ['id' => $defaultAddress->id]);
    assertDatabaseHas('addresses', ['id' => $otherAddress->id]);
    expect($doctor->fresh()->default_address_id)->toBeNull();
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
