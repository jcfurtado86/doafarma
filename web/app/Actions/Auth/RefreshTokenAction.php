<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\TokenAbility;
use App\Enums\UserStatus;
use App\Models\User;
use App\Values\TokenName;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RefreshTokenAction
{
    /**
     * Refresh the access token using a valid refresh token.
     *
     * Note: Sanctum's currentAccessToken() returns the token that authenticated
     * the current request, regardless of its ability. In this context, it's the
     * refresh token since this endpoint requires 'abilities:refresh' middleware.
     *
     * @return array{access_token: string, expires_in: int}
     */
    public function execute(User $user): array
    {
        if ($user->status !== UserStatus::Approved) {
            throw new AccessDeniedHttpException('Seu cadastro não está aprovado.');
        }

        $currentToken          = $user->currentAccessToken();
        $deviceName            = TokenName::fromStoredName($currentToken->name)->device;
        $accessExpirationHours = config('sanctum.access_token_expiration_hours');

        $accessToken = $user->createToken(
            name: TokenName::forNewToken($deviceName, TokenAbility::Access)->toString(),
            abilities: [TokenAbility::Access->value],
            expiresAt: CarbonImmutable::now()->addHours($accessExpirationHours)
        );

        return [
            'access_token' => $accessToken->plainTextToken,
            'expires_in'   => (int) CarbonInterval::hours($accessExpirationHours)->totalSeconds,
        ];
    }
}
