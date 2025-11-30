<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\postJson;

/*
|--------------------------------------------------------------------------
| Login Authentication Tests
|--------------------------------------------------------------------------
|
| Test suite for user login endpoint following TDD approach.
| Tests cover: successful login, validation errors, security and rate limiting.
|
*/

beforeEach(function (): void {
    // Clear rate limiter before each test
    RateLimiter::clear('login:test@example.com|127.0.0.1');
});

describe('Login - Success Scenarios', function (): void {
    it('should login successfully and return token with user data', function (): void {
        $user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('Password123!'),
            'role'     => 'receptor',
        ]);

        $response = postJson(route('api.login'), [
            'email'       => 'user@example.com',
            'password'    => 'Password123!',
            'device_name' => 'iPhone 15',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ],
        ]);

        // Verify token was created
        expect($user->fresh()->tokens)->toHaveCount(1);
    });

    it('should return correct role for doctor user', function (): void {
        User::factory()->create([
            'email'    => 'doctor@example.com',
            'password' => Hash::make('Password123!'),
            'role'     => 'doctor',
        ]);

        $response = postJson(route('api.login'), [
            'email'       => 'doctor@example.com',
            'password'    => 'Password123!',
            'device_name' => 'Samsung Galaxy',
        ]);

        $response->assertOk();
        expect($response->json('data.user.role'))->toBe('doctor');
    });

    it('should return correct role for receptor user', function (): void {
        User::factory()->create([
            'email'    => 'receptor@example.com',
            'password' => Hash::make('Password123!'),
            'role'     => 'receptor',
        ]);

        $response = postJson(route('api.login'), [
            'email'       => 'receptor@example.com',
            'password'    => 'Password123!',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertOk();
        expect($response->json('data.user.role'))->toBe('receptor');
    });

    it('should login with case-insensitive email', function (): void {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = postJson(route('api.login'), [
            'email'       => 'USER@EXAMPLE.COM',
            'password'    => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertOk();
    });

    it('should NOT return sensitive data in response', function (): void {
        User::factory()->create([
            'email'    => 'secure@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = postJson(route('api.login'), [
            'email'       => 'secure@example.com',
            'password'    => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertOk();
        $userData = $response->json('data.user');

        expect($userData)->not->toHaveKey('password');
        expect($userData)->not->toHaveKey('remember_token');
    });
});

describe('Login - Validation Errors', function (): void {
    it('should return 422 when email is missing', function (): void {
        $response = postJson(route('api.login'), [
            'password'    => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    });

    it('should return 422 when password is missing', function (): void {
        $response = postJson(route('api.login'), [
            'email'       => 'user@example.com',
            'device_name' => 'Test Device',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    });

    it('should return 422 when device_name is missing', function (): void {
        $response = postJson(route('api.login'), [
            'email'    => 'user@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['device_name']);
    });

    it('should return 422 when email format is invalid', function (): void {
        $response = postJson(route('api.login'), [
            'email'       => 'invalid-email',
            'password'    => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    });
});

describe('Login - Authentication Failures', function (): void {
    it('should return 422 when email does not exist', function (): void {
        $response = postJson(route('api.login'), [
            'email'       => 'nonexistent@example.com',
            'password'    => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    });

    it('should return 422 when password is incorrect', function (): void {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = postJson(route('api.login'), [
            'email'       => 'user@example.com',
            'password'    => 'WrongPassword123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    });

    it('should return generic error message for security (no email enumeration)', function (): void {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        // Wrong password
        $responseWrongPassword = postJson(route('api.login'), [
            'email'       => 'user@example.com',
            'password'    => 'WrongPassword!',
            'device_name' => 'Test Device',
        ]);

        // Clear rate limiter between attempts
        RateLimiter::clear('login:user@example.com|127.0.0.1');
        RateLimiter::clear('login:nonexistent@example.com|127.0.0.1');

        // Nonexistent email
        $responseWrongEmail = postJson(route('api.login'), [
            'email'       => 'nonexistent@example.com',
            'password'    => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        // Both should return the same error message (prevent email enumeration)
        expect($responseWrongPassword->json('errors.email.0'))
            ->toBe($responseWrongEmail->json('errors.email.0'));
    });
});

describe('Login - Rate Limiting (Security)', function (): void {
    it('should block after 5 failed attempts', function (): void {
        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            postJson(route('api.login'), [
                'email'       => 'test@example.com',
                'password'    => 'WrongPassword!',
                'device_name' => 'Test Device',
            ]);
        }

        // 6th attempt should be blocked
        $response = postJson(route('api.login'), [
            'email'       => 'test@example.com',
            'password'    => 'WrongPassword!',
            'device_name' => 'Test Device',
        ]);

        $response->assertUnprocessable();
        expect($response->json('errors.email.0'))->toContain('Too many');
    });

    it('should allow login after rate limit expires', function (): void {
        User::factory()->create([
            'email'    => 'throttle@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        // Simulate rate limit
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit('login:throttle@example.com|127.0.0.1');
        }

        // Clear rate limiter (simulating time passing)
        RateLimiter::clear('login:throttle@example.com|127.0.0.1');

        // Should work again
        $response = postJson(route('api.login'), [
            'email'       => 'throttle@example.com',
            'password'    => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertOk();
    });

    it('should clear rate limit on successful login', function (): void {
        User::factory()->create([
            'email'    => 'clearrate@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        // Make 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            postJson(route('api.login'), [
                'email'       => 'clearrate@example.com',
                'password'    => 'WrongPassword!',
                'device_name' => 'Test Device',
            ]);
        }

        // Successful login
        postJson(route('api.login'), [
            'email'       => 'clearrate@example.com',
            'password'    => 'Password123!',
            'device_name' => 'Test Device',
        ])->assertOk();

        // Rate limiter should be cleared - another 5 failures should work
        for ($i = 0; $i < 4; $i++) {
            $response = postJson(route('api.login'), [
                'email'       => 'clearrate@example.com',
                'password'    => 'WrongPassword!',
                'device_name' => 'Test Device',
            ]);

            // Should not be rate limited yet
            expect($response->json('errors.email.0'))->not->toContain('Too many');
        }
    });
});

describe('Login - Security Tests', function (): void {
    it('should handle SQL injection attempts safely', function (): void {
        $response = postJson(route('api.login'), [
            'email'       => "admin@example.com'; DROP TABLE users;--",
            'password'    => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        // Should reject as invalid email format
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    });

    it('should handle XSS attempts in email field', function (): void {
        $response = postJson(route('api.login'), [
            'email'       => '<script>alert("xss")</script>@example.com',
            'password'    => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    });
});
