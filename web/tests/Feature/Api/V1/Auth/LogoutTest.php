<?php

declare(strict_types = 1);

use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\PersonalAccessToken;

describe('POST /api/v1/auth/logout', function (): void {
    describe('happy path', function (): void {
        it('should revoke tokens and return success', function (): void {
            $user = User::factory()->approved()->create();

            $accessToken = $user->createToken(
                name: 'test-device:access',
                abilities: ['access'],
                expiresAt: CarbonImmutable::now()->addHour()
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken->plainTextToken,
            ])->postJson('/api/v1/auth/logout');

            $response->assertOk();
            $response->assertJson([
                'message' => 'Logout realizado com sucesso',
            ]);
        });

        it('should revoke all tokens for the device', function (): void {
            $user = User::factory()->approved()->create();

            // Criar par de tokens (access + refresh) para um device
            $accessToken = $user->createToken(
                name: 'iPhone-15:access',
                abilities: ['access'],
                expiresAt: CarbonImmutable::now()->addHour()
            );

            $refreshToken = $user->createToken(
                name: 'iPhone-15:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            // Verificar que os tokens existem
            expect(PersonalAccessToken::where('name', 'iPhone-15:access')->exists())->toBeTrue();
            expect(PersonalAccessToken::where('name', 'iPhone-15:refresh')->exists())->toBeTrue();

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken->plainTextToken,
            ])->postJson('/api/v1/auth/logout');

            $response->assertOk();

            // Verificar que ambos tokens foram revogados
            expect(PersonalAccessToken::where('name', 'iPhone-15:access')->exists())->toBeFalse();
            expect(PersonalAccessToken::where('name', 'iPhone-15:refresh')->exists())->toBeFalse();
        });

        it('should not revoke tokens from other devices', function (): void {
            $user = User::factory()->approved()->create();

            // Tokens do iPhone
            $iphoneAccess = $user->createToken(
                name: 'iPhone-15:access',
                abilities: ['access'],
                expiresAt: CarbonImmutable::now()->addHour()
            );
            $user->createToken(
                name: 'iPhone-15:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            // Tokens do Android (outro device)
            $user->createToken(
                name: 'Android-Phone:access',
                abilities: ['access'],
                expiresAt: CarbonImmutable::now()->addHour()
            );
            $user->createToken(
                name: 'Android-Phone:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            // Logout do iPhone
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $iphoneAccess->plainTextToken,
            ])->postJson('/api/v1/auth/logout');

            $response->assertOk();

            // Tokens do iPhone foram revogados
            expect(PersonalAccessToken::where('name', 'iPhone-15:access')->exists())->toBeFalse();
            expect(PersonalAccessToken::where('name', 'iPhone-15:refresh')->exists())->toBeFalse();

            // Tokens do Android ainda existem
            expect(PersonalAccessToken::where('name', 'Android-Phone:access')->exists())->toBeTrue();
            expect(PersonalAccessToken::where('name', 'Android-Phone:refresh')->exists())->toBeTrue();
        });
    });

    describe('post logout behavior', function (): void {
        it('should delete access and refresh tokens from database', function (): void {
            $user = User::factory()->approved()->create();

            $accessToken = $user->createToken(
                name: 'test-device:access',
                abilities: ['access'],
                expiresAt: CarbonImmutable::now()->addHour()
            );

            $user->createToken(
                name: 'test-device:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            // Verificar que tokens existem antes do logout
            expect(PersonalAccessToken::where('name', 'test-device:access')->exists())->toBeTrue();
            expect(PersonalAccessToken::where('name', 'test-device:refresh')->exists())->toBeTrue();

            // Fazer logout
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken->plainTextToken,
            ])->postJson('/api/v1/auth/logout');

            // Verificar que AMBOS os tokens foram deletados do banco
            expect(PersonalAccessToken::where('name', 'test-device:access')->exists())->toBeFalse();
            expect(PersonalAccessToken::where('name', 'test-device:refresh')->exists())->toBeFalse();

            // O Sanctum automaticamente rejeita tokens que não existem no banco
            // Não precisamos testar isso aqui - é comportamento interno do Sanctum
        });
    });

    describe('authentication required', function (): void {
        it('should reject logout without token', function (): void {
            $response = $this->postJson('/api/v1/auth/logout');

            $response->assertUnauthorized();
        });

        it('should reject logout with invalid token', function (): void {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer invalid-token',
            ])->postJson('/api/v1/auth/logout');

            $response->assertUnauthorized();
        });

        it('should reject logout with refresh token', function (): void {
            $user = User::factory()->approved()->create();

            // Usar refresh token ao invés de access token
            $refreshToken = $user->createToken(
                name: 'test-device:refresh',
                abilities: ['refresh'],
                expiresAt: CarbonImmutable::now()->addDays(30)
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken->plainTextToken,
            ])->postJson('/api/v1/auth/logout');

            // Deve falhar porque refresh token não tem ability 'access'
            $response->assertForbidden();
        });
    });
});
