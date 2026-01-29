<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\TokenAbility;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;

class CreateTokenPairAction
{
    /**
     * Create a pair of tokens (access + refresh) for the user.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int, refresh_expires_in: int}
     */
    public function execute(User $user, string $deviceName): array
    {
        $now                   = CarbonImmutable::now();
        $accessExpirationHours = config('sanctum.access_token_expiration_hours');
        $refreshExpirationDays = config('sanctum.refresh_token_expiration_days');

        $accessToken = $user->createToken(
            name: "{$deviceName}:" . TokenAbility::Access->value,
            abilities: [TokenAbility::Access->value],
            expiresAt: $now->addHours($accessExpirationHours)
        );

        $refreshToken = $user->createToken(
            name: "{$deviceName}:" . TokenAbility::Refresh->value,
            abilities: [TokenAbility::Refresh->value],
            expiresAt: $now->addDays($refreshExpirationDays)
        );

        return [
            'access_token'       => $accessToken->plainTextToken,
            'refresh_token'      => $refreshToken->plainTextToken,
            'expires_in'         => (int) CarbonInterval::hours($accessExpirationHours)->totalSeconds,
            'refresh_expires_in' => (int) CarbonInterval::days($refreshExpirationDays)->totalSeconds,
        ];
    }
}
