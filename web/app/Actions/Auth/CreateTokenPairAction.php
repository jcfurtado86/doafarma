<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Models\User;
use Carbon\CarbonImmutable;

class CreateTokenPairAction
{
    public const ACCESS_EXPIRATION_HOURS = 1;

    public const REFRESH_EXPIRATION_DAYS = 30;

    /**
     * Create a pair of tokens (access + refresh) for the user.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int, refresh_expires_in: int}
     */
    public function execute(User $user, string $deviceName): array
    {
        $now = CarbonImmutable::now();

        $accessToken = $user->createToken(
            name: "{$deviceName}:access",
            abilities: ['access'],
            expiresAt: $now->addHours(self::ACCESS_EXPIRATION_HOURS)
        );

        $refreshToken = $user->createToken(
            name: "{$deviceName}:refresh",
            abilities: ['refresh'],
            expiresAt: $now->addDays(self::REFRESH_EXPIRATION_DAYS)
        );

        return [
            'access_token'       => $accessToken->plainTextToken,
            'refresh_token'      => $refreshToken->plainTextToken,
            'expires_in'         => self::ACCESS_EXPIRATION_HOURS * 3600,
            'refresh_expires_in' => self::REFRESH_EXPIRATION_DAYS * 86400,
        ];
    }
}
