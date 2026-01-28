<?php

declare(strict_types = 1);

use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\PersonalAccessToken;

describe('POST /api/v1/auth/login - token pair', function (): void {
    describe('response structure', function (): void {
        it('should return access_token and refresh_token on successful login', function (): void {
            $user = User::factory()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'       => $user->email,
                'password'    => 'password',
                'device_name' => 'iPhone-15',
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
        });

        it('should return correct expiration times', function (): void {
            $user = User::factory()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            $data = $response->json('data');

            // Access token expira em 1 hora (3600 segundos)
            expect($data['expires_in'])->toBe(3600);

            // Refresh token expira em 30 dias (2592000 segundos)
            expect($data['refresh_expires_in'])->toBe(2592000);
        });

        it('should use default device name when not provided', function (): void {
            $user = User::factory()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            $response->assertSuccessful();

            // Verificar que tokens foram criados com device name padrão
            expect(PersonalAccessToken::where('name', 'mobile-app:access')->exists())->toBeTrue();
            expect(PersonalAccessToken::where('name', 'mobile-app:refresh')->exists())->toBeTrue();
        });
    });

    describe('token creation', function (): void {
        it('should create access token with correct abilities', function (): void {
            $user = User::factory()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'       => $user->email,
                'password'    => 'password',
                'device_name' => 'test-device',
            ]);

            $accessTokenRecord = PersonalAccessToken::where('name', 'test-device:access')->first();

            expect($accessTokenRecord)->not->toBeNull();
            expect($accessTokenRecord->abilities)->toBe(['access']);
        });

        it('should create refresh token with correct abilities', function (): void {
            $user = User::factory()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'       => $user->email,
                'password'    => 'password',
                'device_name' => 'test-device',
            ]);

            $refreshTokenRecord = PersonalAccessToken::where('name', 'test-device:refresh')->first();

            expect($refreshTokenRecord)->not->toBeNull();
            expect($refreshTokenRecord->abilities)->toBe(['refresh']);
        });

        it('should create access token with correct expiration', function (): void {
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-28 12:00:00'));

            $user = User::factory()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'       => $user->email,
                'password'    => 'password',
                'device_name' => 'test-device',
            ]);

            $accessTokenRecord = PersonalAccessToken::where('name', 'test-device:access')->first();

            expect($accessTokenRecord->expires_at)->not->toBeNull();
            expect($accessTokenRecord->expires_at->toDateTimeString())->toBe('2026-01-28 13:00:00');

            CarbonImmutable::setTestNow();
        });

        it('should create refresh token with correct expiration', function (): void {
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-28 12:00:00'));

            $user = User::factory()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'       => $user->email,
                'password'    => 'password',
                'device_name' => 'test-device',
            ]);

            $refreshTokenRecord = PersonalAccessToken::where('name', 'test-device:refresh')->first();

            expect($refreshTokenRecord->expires_at)->not->toBeNull();
            expect($refreshTokenRecord->expires_at->toDateTimeString())->toBe('2026-02-27 12:00:00');

            CarbonImmutable::setTestNow();
        });
    });

    describe('token usage', function (): void {
        it('should allow authenticated requests with access token', function (): void {
            $user = User::factory()->approved()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            $accessToken = $response->json('data.access_token');

            $authenticatedResponse = $this->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->getJson(route('api.v1.drugs.search'));

            $authenticatedResponse->assertSuccessful();
        });

        it('should not allow refresh token for logout endpoint', function (): void {
            $user = User::factory()->approved()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            $refreshToken = $response->json('data.refresh_token');

            // Limpar sessão web para simular requisições independentes (como app mobile)
            auth()->guard('web')->logout();

            // Refresh token não deve funcionar para logout (requer abilities:access)
            $logoutResponse = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken,
            ])->postJson('/api/v1/auth/logout');

            $logoutResponse->assertForbidden();
        });

        it('should allow refresh with refresh token', function (): void {
            $user = User::factory()->approved()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            $refreshToken = $response->json('data.refresh_token');

            // Limpar sessão web para simular requisições independentes (como app mobile)
            auth()->guard('web')->logout();

            $refreshResponse = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken,
            ])->postJson('/api/v1/auth/refresh');

            $refreshResponse->assertOk();
            $refreshResponse->assertJsonStructure([
                'data' => [
                    'access_token',
                    'expires_in',
                ],
            ]);
        });
    });

    describe('validation', function (): void {
        it('should accept device_name as optional parameter', function (): void {
            $user = User::factory()->create();

            // Sem device_name
            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            $response->assertSuccessful();
        });

        it('should reject device_name longer than 255 characters', function (): void {
            $user = User::factory()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'       => $user->email,
                'password'    => 'password',
                'device_name' => str_repeat('a', 256),
            ]);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['device_name']);
        });
    });
});
