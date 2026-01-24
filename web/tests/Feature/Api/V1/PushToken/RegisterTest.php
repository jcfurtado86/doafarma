<?php

declare(strict_types = 1);

use App\Models\PushToken;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('should register a new push token for authenticated user', function (): void {
    $user  = User::factory()->create();
    $token = 'ExponentPushToken[' . fake()->uuid() . ']';

    actingAs($user, 'sanctum')
        ->postJson('/api/v1/push-tokens', [
            'token'       => $token,
            'device_type' => 'android',
        ])
        ->assertSuccessful()
        ->assertJson([
            'message' => 'Token registrado com sucesso.',
        ]);

    $this->assertDatabaseHas('push_tokens', [
        'user_id'     => $user->id,
        'token'       => $token,
        'device_type' => 'android',
    ]);
});

it('should update push token if it already exists for another user', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $token = 'ExponentPushToken[' . fake()->uuid() . ']';

    // First user registers the token
    PushToken::create([
        'user_id'     => $user1->id,
        'token'       => $token,
        'device_type' => 'ios',
    ]);

    // Second user tries to register the same token (e.g., after login)
    actingAs($user2, 'sanctum')
        ->postJson('/api/v1/push-tokens', [
            'token'       => $token,
            'device_type' => 'android',
        ])
        ->assertSuccessful();

    // Token should now belong to user2
    $this->assertDatabaseHas('push_tokens', [
        'user_id'     => $user2->id,
        'token'       => $token,
        'device_type' => 'android',
    ]);

    // Only one token with this value should exist
    expect(PushToken::where('token', $token)->count())->toBe(1);
});

it('should allow registering token without device_type', function (): void {
    $user  = User::factory()->create();
    $token = 'ExponentPushToken[' . fake()->uuid() . ']';

    actingAs($user, 'sanctum')
        ->postJson('/api/v1/push-tokens', [
            'token' => $token,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('push_tokens', [
        'user_id'     => $user->id,
        'token'       => $token,
        'device_type' => null,
    ]);
});

it('should require authentication to register push token', function (): void {
    $token = 'ExponentPushToken[' . fake()->uuid() . ']';

    $this->postJson('/api/v1/push-tokens', [
        'token' => $token,
    ])->assertUnauthorized();
});

it('should require token field', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum')
        ->postJson('/api/v1/push-tokens', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token']);
});

it('should validate device_type values', function (): void {
    $user  = User::factory()->create();
    $token = 'ExponentPushToken[' . fake()->uuid() . ']';

    actingAs($user, 'sanctum')
        ->postJson('/api/v1/push-tokens', [
            'token'       => $token,
            'device_type' => 'windows',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['device_type']);
});

it('should allow multiple tokens per user', function (): void {
    $user   = User::factory()->create();
    $token1 = 'ExponentPushToken[' . fake()->uuid() . ']';
    $token2 = 'ExponentPushToken[' . fake()->uuid() . ']';

    actingAs($user, 'sanctum')
        ->postJson('/api/v1/push-tokens', ['token' => $token1])
        ->assertSuccessful();

    actingAs($user, 'sanctum')
        ->postJson('/api/v1/push-tokens', ['token' => $token2])
        ->assertSuccessful();

    expect(PushToken::where('user_id', $user->id)->count())->toBe(2);
});
