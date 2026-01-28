<?php

declare(strict_types = 1);

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken;

describe('token configuration', function (): void {
    describe('access token expiration config', function (): void {
        it('should use configured expiration hours for access token', function (): void {
            Config::set('sanctum.access_token_expiration_hours', 2);

            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-28 10:00:00'));

            $user = User::factory()->approved()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            $response->assertOk();

            $accessTokenString = $response->json('data.access_token');
            $tokenId           = explode('|', $accessTokenString)[0];
            $token             = PersonalAccessToken::find($tokenId);

            expect($token->expires_at->toDateTimeString())->toBe('2026-01-28 12:00:00');
            expect($response->json('data.expires_in'))->toBe(7200);

            CarbonImmutable::setTestNow();
        });

        it('should use default 1 hour when config is not set', function (): void {
            Config::set('sanctum.access_token_expiration_hours', 1);

            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-28 10:00:00'));

            $user = User::factory()->approved()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            $response->assertOk();
            expect($response->json('data.expires_in'))->toBe(3600);

            CarbonImmutable::setTestNow();
        });
    });

    describe('refresh token expiration config', function (): void {
        it('should use configured expiration days for refresh token', function (): void {
            Config::set('sanctum.refresh_token_expiration_days', 7);

            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-28 10:00:00'));

            $user = User::factory()->approved()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            $response->assertOk();

            $refreshTokenString = $response->json('data.refresh_token');
            $tokenId            = explode('|', $refreshTokenString)[0];
            $token              = PersonalAccessToken::find($tokenId);

            expect($token->expires_at->toDateTimeString())->toBe('2026-02-04 10:00:00');
            expect($response->json('data.refresh_expires_in'))->toBe((int) CarbonInterval::days(7)->totalSeconds);

            CarbonImmutable::setTestNow();
        });

        it('should use default 30 days when config is not set', function (): void {
            Config::set('sanctum.refresh_token_expiration_days', 30);

            $user = User::factory()->approved()->create();

            $response = $this->postJson('/api/v1/auth/login', [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            $response->assertOk();
            expect($response->json('data.refresh_expires_in'))->toBe((int) CarbonInterval::days(30)->totalSeconds);
        });
    });

    describe('refresh endpoint respects config', function (): void {
        it('should create new access token with configured expiration', function (): void {
            Config::set('sanctum.access_token_expiration_hours', 3);

            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-28 10:00:00'));

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

            $accessTokenString = $response->json('data.access_token');
            $tokenId           = explode('|', $accessTokenString)[0];
            $token             = PersonalAccessToken::find($tokenId);

            expect($token->expires_at->toDateTimeString())->toBe('2026-01-28 13:00:00');
            expect($response->json('data.expires_in'))->toBe(10800);

            CarbonImmutable::setTestNow();
        });
    });
});
