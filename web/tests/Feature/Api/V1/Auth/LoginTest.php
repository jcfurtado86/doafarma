<?php

declare(strict_types = 1);

use App\Models\User;
use Illuminate\Http\Response;

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
            'access_token',
            'refresh_token',
            'expires_in',
            'refresh_expires_in',
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
    expect($responseData['access_token'])->toBeString();
    expect($responseData['refresh_token'])->toBeString();

    $token                 = $responseData['access_token'];
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

it('should create tokens with correct abilities via API v1', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful();

    // Deve criar 2 tokens: access e refresh
    expect($user->tokens()->count())->toBe(2);

    $accessToken  = $user->tokens()->where('name', 'like', '%:access')->first();
    $refreshToken = $user->tokens()->where('name', 'like', '%:refresh')->first();

    expect($accessToken)->not->toBeNull();
    expect($accessToken->abilities)->toBe(['access']);

    expect($refreshToken)->not->toBeNull();
    expect($refreshToken->abilities)->toBe(['refresh']);
});

it('should rate limit login attempts via API v1', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
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
