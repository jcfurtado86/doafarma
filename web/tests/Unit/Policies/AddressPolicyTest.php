<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;
use App\Policies\AddressPolicy;

beforeEach(function (): void {
    $this->policy = new AddressPolicy();
});

it('allows any authenticated user to view any addresses list', function (): void {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeTrue();
});

it('allows any authenticated user to create an address', function (): void {
    $user = User::factory()->create();

    expect($this->policy->create($user))->toBeTrue();
});

it('allows a user to update their own address', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    expect($this->policy->update($user, $address))->toBeTrue();
});

it('denies a user from updating another user\'s address', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    expect($this->policy->update($user, $otherAddress))->toBeFalse();
});

it('allows a user to set their own address as default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    expect($this->policy->setDefault($user, $address))->toBeTrue();
});

it('denies a user from setting another user\'s address as default', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    expect($this->policy->setDefault($user, $otherAddress))->toBeFalse();
});

it('allows a user to delete their own address', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    expect($this->policy->delete($user, $address))->toBeTrue();
});

it('denies a user from deleting another user\'s address', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    expect($this->policy->delete($user, $otherAddress))->toBeFalse();
});
