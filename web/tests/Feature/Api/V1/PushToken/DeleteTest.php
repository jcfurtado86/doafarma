<?php

declare(strict_types = 1);

use App\Models\PushToken;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('should delete push token for authenticated user', function (): void {
    $user  = User::factory()->create();
    $token = 'ExponentPushToken[' . fake()->uuid() . ']';

    PushToken::create([
        'user_id'     => $user->id,
        'token'       => $token,
        'device_type' => 'android',
    ]);

    actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/push-tokens', [
            'token' => $token,
        ])
        ->assertSuccessful()
        ->assertJson([
            'message' => 'Token removido com sucesso.',
        ]);

    $this->assertDatabaseMissing('push_tokens', [
        'user_id' => $user->id,
        'token'   => $token,
    ]);
});

it('should not delete other user push token', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $token = 'ExponentPushToken[' . fake()->uuid() . ']';

    PushToken::create([
        'user_id'     => $user1->id,
        'token'       => $token,
        'device_type' => 'android',
    ]);

    actingAs($user2, 'sanctum')
        ->deleteJson('/api/v1/push-tokens', [
            'token' => $token,
        ])
        ->assertSuccessful();

    // Token should still exist for user1
    $this->assertDatabaseHas('push_tokens', [
        'user_id' => $user1->id,
        'token'   => $token,
    ]);
});

it('should require authentication to delete push token', function (): void {
    $token = 'ExponentPushToken[' . fake()->uuid() . ']';

    $this->deleteJson('/api/v1/push-tokens', [
        'token' => $token,
    ])->assertUnauthorized();
});

it('should handle deleting non-existent token gracefully', function (): void {
    $user  = User::factory()->create();
    $token = 'ExponentPushToken[' . fake()->uuid() . ']';

    actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/push-tokens', [
            'token' => $token,
        ])
        ->assertSuccessful();
});
