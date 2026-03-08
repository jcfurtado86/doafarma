<?php

declare(strict_types = 1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\postJson;

it('should reset password with a valid token', function (): void {
    $user  = User::factory()->create();
    $token = Password::createToken($user);

    $response = postJson(route('api.v1.auth.reset-password'), [
        'token'                 => $token,
        'email'                 => $user->email,
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertOk();
    $response->assertJson([
        'message' => 'Your password has been reset successfully.',
    ]);

    $user->refresh();
    expect(Hash::check('new-password-123', $user->password))->toBeTrue();
});

it('should allow login with new password after reset', function (): void {
    $user  = User::factory()->create();
    $token = Password::createToken($user);

    postJson(route('api.v1.auth.reset-password'), [
        'token'                 => $token,
        'email'                 => $user->email,
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $loginResponse = postJson(route('api.v1.auth.login'), [
        'email'    => $user->email,
        'password' => 'new-password-123',
    ]);

    $loginResponse->assertSuccessful();
});

it('should revoke all existing sanctum tokens after reset', function (): void {
    $user = User::factory()->create();

    $user->createToken('device:access', ['access']);
    $user->createToken('device:refresh', ['refresh']);
    expect($user->tokens()->count())->toBe(2);

    $token = Password::createToken($user);

    postJson(route('api.v1.auth.reset-password'), [
        'token'                 => $token,
        'email'                 => $user->email,
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

it('should reject an invalid token', function (): void {
    $user = User::factory()->create();

    $response = postJson(route('api.v1.auth.reset-password'), [
        'token'                 => 'invalid-token',
        'email'                 => $user->email,
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

it('should reject an expired token', function (): void {
    $user  = User::factory()->create();
    $token = Password::createToken($user);

    $this->travel(61)->minutes();

    $response = postJson(route('api.v1.auth.reset-password'), [
        'token'                 => $token,
        'email'                 => $user->email,
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

it('should reject when email does not match the token', function (): void {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    $token = Password::createToken($user);

    $response = postJson(route('api.v1.auth.reset-password'), [
        'token'                 => $token,
        'email'                 => $other->email,
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

it('should reject when password confirmation does not match', function (): void {
    $user  = User::factory()->create();
    $token = Password::createToken($user);

    $response = postJson(route('api.v1.auth.reset-password'), [
        'token'                 => $token,
        'email'                 => $user->email,
        'password'              => 'new-password-123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['password']);
});

it('should require all fields', function (): void {
    $response = postJson(route('api.v1.auth.reset-password'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['token', 'email', 'password']);
});

it('should rate limit after 3 requests per minute', function (): void {
    $user  = User::factory()->create();
    $token = Password::createToken($user);

    for ($i = 0; $i < 3; $i++) {
        postJson(route('api.v1.auth.reset-password'), [
            'token'                 => 'invalid-token',
            'email'                 => $user->email,
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);
    }

    $response = postJson(route('api.v1.auth.reset-password'), [
        'token'                 => $token,
        'email'                 => $user->email,
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertStatus(429);
    $response->assertJson([
        'message' => 'Too many password reset requests. Please try again later.',
        'errors'  => [
            'email' => ['Too many password reset requests. Please try again later.'],
        ],
    ]);
});
