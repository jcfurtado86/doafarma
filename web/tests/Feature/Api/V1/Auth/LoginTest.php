<?php

declare(strict_types = 1);

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertGuest;

it('should authenticate users via API v1 login', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            'user' => [
                'id',
                'name',
                'email',
            ],
            'token',
        ],
        'message',
    ]);

    assertDatabaseHas('personal_access_tokens', [
        'tokenable_id'   => $user->id,
        'tokenable_type' => User::class,
    ]);

    $responseData = $response->json('data');
    expect($responseData['user']['id'])->toBe($user->id);
    expect($responseData['user']['email'])->toBe($user->email);
    expect($responseData['user']['name'])->toBe($user->name);
    expect($responseData['token'])->toBeString();

    $token                 = $responseData['token'];
    $authenticatedResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson(route('api.v1.drugs.search'));

    $authenticatedResponse->assertSuccessful();

    $this->assertAuthenticated('sanctum');
});

it('should not authenticate users with invalid password via API v1', function (): void {
    $user = User::factory()->create();

    assertGuest();

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);

    assertGuest();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id'   => $user->id,
        'tokenable_type' => User::class,
    ]);
});

it('should not authenticate users with invalid email via API v1', function (): void {
    assertGuest();

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'nonexistent@example.com',
        'password' => 'password',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);

    assertGuest();
});

it('should require email and password for login via API v1', function (): void {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email', 'password']);
});

it('should require valid email format for login via API v1', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'invalid-email',
        'password' => 'password',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

it('should not authenticate with empty password via API v1', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => '',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['password']);
});

it('should not authenticate with empty email via API v1', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => '',
        'password' => 'password',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

it('should return proper error message for invalid credentials via API v1', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable();
    $response->assertJson([
        'message' => 'These credentials do not match our records.',
        'errors'  => [
            'email' => ['These credentials do not match our records.'],
        ],
    ]);
});

it('should create token with correct abilities via API v1', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful();

    $token = $user->tokens()->first();
    expect($token)->not->toBeNull();
    expect($token->abilities)->toBe(['*']);
});

it('should rate limit login attempts via API v1', function (): void {
    $user = User::factory()->create();

    RateLimiter::clear('login:' . request()->ip());

    $maxAttempts = config('auth.rate_limiting.max_attempts', 5);

    for ($i = 0; $i < $maxAttempts; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    $response->assertJson([
        'message' => 'Too many login attempts. Please try again later.',
        'errors'  => [
            'email' => ['Too many login attempts. Please try again later.'],
        ],
    ]);
    $response->assertHeader('X-RateLimit-Limit');
    $response->assertHeader('Retry-After');
});
