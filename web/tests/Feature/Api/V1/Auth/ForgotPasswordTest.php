<?php

declare(strict_types = 1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\postJson;

it('should send a password reset link for a valid email', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $response = postJson(route('api.v1.auth.forgot-password'), [
        'email' => $user->email,
    ]);

    $response->assertOk();
    $response->assertJson([
        'message' => 'If an account with that email exists, we have sent a password reset link.',
    ]);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('should return 200 even when email does not exist', function (): void {
    Notification::fake();

    $response = postJson(route('api.v1.auth.forgot-password'), [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertOk();
    $response->assertJson([
        'message' => 'If an account with that email exists, we have sent a password reset link.',
    ]);

    Notification::assertNothingSent();
});

it('should require the email field', function (): void {
    $response = postJson(route('api.v1.auth.forgot-password'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

it('should require a valid email format', function (): void {
    $response = postJson(route('api.v1.auth.forgot-password'), [
        'email' => 'invalid-email',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

it('should rate limit after 3 requests per minute', function (): void {
    for ($i = 0; $i < 3; $i++) {
        postJson(route('api.v1.auth.forgot-password'), [
            'email' => "user{$i}@example.com",
        ]);
    }

    $response = postJson(route('api.v1.auth.forgot-password'), [
        'email' => 'another@example.com',
    ]);

    $response->assertStatus(429);
    $response->assertJson([
        'message' => 'Too many password reset requests. Please try again later.',
        'errors'  => [
            'email' => ['Too many password reset requests. Please try again later.'],
        ],
    ]);
    $response->assertHeader('Retry-After');
});

it('should still return 200 when broker throttles the same email', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $firstResponse = postJson(route('api.v1.auth.forgot-password'), [
        'email' => $user->email,
    ]);

    $firstResponse->assertOk();

    $secondResponse = postJson(route('api.v1.auth.forgot-password'), [
        'email' => $user->email,
    ]);

    $secondResponse->assertOk();
    $secondResponse->assertJson([
        'message' => 'If an account with that email exists, we have sent a password reset link.',
    ]);
});

it('should normalize uppercase email', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $response = postJson(route('api.v1.auth.forgot-password'), [
        'email' => 'USER@EXAMPLE.COM',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});
