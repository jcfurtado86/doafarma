<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('should be accessible via GET /api/v1/addresses', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    getJson('/api/v1/addresses')->assertOk();
    getJson(route('api.v1.addresses.list'))->assertOk();
});

it('returns only the addresses belonging to the authenticated user', function (): void {
    $user      = User::factory()->create();
    $otherUser = User::factory()->create();

    $myAddresses    = Address::factory()->count(2)->create(['user_id' => $user->id]);
    $otherAddresses = Address::factory()->count(3)->create(['user_id' => $otherUser->id]);

    actingAs($user, 'sanctum');

    $response = getJson(route('api.v1.addresses.list'));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');

    $returnedIds = array_column($response->json('data'), 'id');
    expect($returnedIds)->toEqualCanonicalizing($myAddresses->pluck('id')->toArray());
});

it('returns an empty collection when the user has no addresses', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    $response = getJson(route('api.v1.addresses.list'));

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

it('marks the address matching default_address_id as is_default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    $response = getJson(route('api.v1.addresses.list'));

    $response->assertOk();
    $response->assertJson([
        'data' => [
            ['id' => $address->id, 'is_default' => true],
        ],
    ]);
});

it('does not mark an address as default when default_address_id points to another user\'s address', function (): void {
    $viewer       = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    // Bypasses the normal SetDefaultAddressAction/policy flow on purpose: simulates an
    // inconsistent data state (e.g. a future code path, a manual data fix) where
    // default_address_id points to an address that isn't the viewer's own. AddressResource
    // must not blindly trust default_address_id without also confirming the address being
    // rendered belongs to the person looking at it.
    $viewer->update(['default_address_id' => $otherAddress->id]);

    $payload = (new App\Http\Resources\Api\V1\AddressResource($otherAddress))
        ->toArray(Illuminate\Http\Request::create('/', 'GET')->setUserResolver(fn () => $viewer));

    expect($payload['is_default'])->toBeFalse();
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    getJson(route('api.v1.addresses.list'))->assertUnauthorized();
});
