<?php

declare(strict_types = 1);

use App\Models\User;
use Carbon\CarbonImmutable;

describe('token expiration', function (): void {
    describe('access token expiration', function (): void {
        it('should reject expired access token with 401', function (): void {
            $user = User::factory()->approved()->create();

            // Criar access token já expirado
            $accessToken = $user->createToken(
                name: 'test-device:access',
                abilities: ['access'],
                expiresAt: CarbonImmutable::now()->subMinute() // Expirado
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken->plainTextToken,
            ])->getJson(route('api.v1.drugs.search'));

            $response->assertUnauthorized();
        });

        it('should accept valid non-expired access token', function (): void {
            $user = User::factory()->approved()->create();

            // Criar access token válido
            $accessToken = $user->createToken(
                name: 'test-device:access',
                abilities: ['access'],
                expiresAt: CarbonImmutable::now()->addHour()
            );

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken->plainTextToken,
            ])->getJson(route('api.v1.drugs.search'));

            $response->assertSuccessful();
        });

        it('should reject access token about to expire', function (): void {
            $user = User::factory()->approved()->create();

            // Token que expira em 1 segundo
            $accessToken = $user->createToken(
                name: 'test-device:access',
                abilities: ['access'],
                expiresAt: CarbonImmutable::now()->addSecond()
            );

            // Esperar o token expirar
            sleep(2);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken->plainTextToken,
            ])->getJson(route('api.v1.drugs.search'));

            $response->assertUnauthorized();
        });
    });

    describe('token abilities', function (): void {
        it('should reject refresh token used for refresh endpoint when using access token', function (): void {
            $user = User::factory()->approved()->create();

            // Criar access token (não refresh)
            $accessToken = $user->createToken(
                name: 'test-device:access',
                abilities: ['access'],
                expiresAt: CarbonImmutable::now()->addHour()
            );

            // Tentar usar access token para refresh
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken->plainTextToken,
            ])->postJson('/api/v1/auth/refresh');

            // Deve falhar porque access token não tem ability 'refresh'
            $response->assertForbidden();
        });

        it('should allow access token for logout endpoint', function (): void {
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
        });
    });
});
