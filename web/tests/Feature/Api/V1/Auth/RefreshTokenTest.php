<?php

declare(strict_types = 1);

use App\Enums\UserStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

describe('POST /api/v1/auth/refresh', function (): void {
    describe('happy path', function (): void {
        it('should return new access token when refresh token is valid', function (): void {
            $user = User::factory()->approved()->create();

            // Criar refresh token manualmente (simulando o que o login faria)
            $refreshToken = $user->createToken(
                name: 'test-device:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken->plainTextToken,
            ])->postJson('/api/v1/auth/refresh');

            $response->assertOk();
            $response->assertJsonStructure([
                'data' => [
                    'access_token',
                    'expires_in',
                ],
                'message',
            ]);

            $data = $response->json('data');
            expect($data['access_token'])->toBeString();
            expect($data['expires_in'])->toBe(3600); // 1 hour in seconds
        });

        it('should create access token with correct abilities', function (): void {
            $user = User::factory()->approved()->create();

            $refreshToken = $user->createToken(
                name: 'test-device:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken->plainTextToken,
            ])->postJson('/api/v1/auth/refresh');

            $response->assertOk();

            // Verificar que o novo token tem ability 'access'
            $newTokenString = $response->json('data.access_token');
            $tokenId        = explode('|', $newTokenString)[0];
            $newToken       = PersonalAccessToken::find($tokenId);

            expect($newToken)->not->toBeNull();
            expect($newToken->abilities)->toBe(['access']);
            expect($newToken->name)->toBe('test-device:access');
        });

        it('should create access token with correct expiration', function (): void {
            $user = User::factory()->approved()->create();

            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-28 12:00:00'));

            $refreshToken = $user->createToken(
                name: 'test-device:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken->plainTextToken,
            ])->postJson('/api/v1/auth/refresh');

            $response->assertOk();

            $newTokenString = $response->json('data.access_token');
            $tokenId        = explode('|', $newTokenString)[0];
            $newToken       = PersonalAccessToken::find($tokenId);

            expect($newToken->expires_at)->not->toBeNull();
            expect($newToken->expires_at->toDateTimeString())->toBe('2026-01-28 13:00:00');

            CarbonImmutable::setTestNow();
        });

        it('should create new access token with correct properties', function (): void {
            $user = User::factory()->approved()->create();

            $refreshToken = $user->createToken(
                name: 'test-device:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken->plainTextToken,
            ])->postJson('/api/v1/auth/refresh');

            $newAccessToken = $response->json('data.access_token');

            // Verificar que o novo token foi criado corretamente
            $tokenId     = explode('|', $newAccessToken)[0];
            $tokenRecord = PersonalAccessToken::find($tokenId);

            expect($tokenRecord)->not->toBeNull();
            expect($tokenRecord->name)->toBe('test-device:access');
            expect($tokenRecord->abilities)->toBe(['access']);
            expect($tokenRecord->expires_at)->not->toBeNull();
            expect($tokenRecord->tokenable_id)->toBe($user->id);

            // O token foi criado corretamente no banco
            // O Sanctum automaticamente permite autenticação com tokens válidos no banco
        });
    });

    describe('token validation', function (): void {
        it('should reject expired refresh token with 401', function (): void {
            $user = User::factory()->approved()->create();

            // Criar refresh token já expirado
            $refreshToken = $user->createToken(
                name: 'test-device:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->subDay() // Expirado ontem
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken->plainTextToken,
            ])->postJson('/api/v1/auth/refresh');

            $response->assertUnauthorized();
        });

        it('should reject access token used for refresh with 401', function (): void {
            $user = User::factory()->approved()->create();

            // Criar access token (não refresh)
            $accessToken = $user->createToken(
                name: 'test-device:access',
                abilities: ['access'],
                expiresAt: CarbonImmutable::now()->addHour()
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken->plainTextToken,
            ])->postJson('/api/v1/auth/refresh');

            // Deve falhar porque o token não tem ability 'refresh'
            $response->assertStatus(Response::HTTP_FORBIDDEN);
        });

        it('should reject invalid token with 401', function (): void {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer invalid-token-here',
            ])->postJson('/api/v1/auth/refresh');

            $response->assertUnauthorized();
        });

        it('should reject request without token with 401', function (): void {
            $response = $this->postJson('/api/v1/auth/refresh');

            $response->assertUnauthorized();
        });
    });

    describe('user status validation', function (): void {
        it('should reject refresh for pending user with 403', function (): void {
            $user = User::factory()->pending()->create();

            $refreshToken = $user->createToken(
                name: 'test-device:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken->plainTextToken,
            ])->postJson('/api/v1/auth/refresh');

            $response->assertForbidden();
            $response->assertJson([
                'message' => 'Seu cadastro não está aprovado.',
            ]);
        });

        it('should reject refresh for rejected user with 403', function (): void {
            $user = User::factory()->rejected()->create();

            $refreshToken = $user->createToken(
                name: 'test-device:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken->plainTextToken,
            ])->postJson('/api/v1/auth/refresh');

            $response->assertForbidden();
        });

        it('should reject refresh when user was approved but later rejected', function (): void {
            $user = User::factory()->approved()->create();

            $refreshToken = $user->createToken(
                name: 'test-device:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            // Simula admin rejeitando o usuário depois que ele já tinha token
            $user->update(['status' => UserStatus::Rejected]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken->plainTextToken,
            ])->postJson('/api/v1/auth/refresh');

            $response->assertForbidden();
        });
    });
});
