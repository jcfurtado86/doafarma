<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RefreshTokenAction
{
    /**
     * Refresh the access token using a valid refresh token.
     *
     * @return array{access_token: string, expires_in: int}
     */
    public function execute(User $user): array
    {
        // Verificar se user ainda está aprovado
        if ($user->status !== UserStatus::Approved) {
            throw new AccessDeniedHttpException('Seu cadastro não está aprovado.');
        }

        // Extrair device name do refresh token atual
        $currentToken = $user->currentAccessToken();

        // @phpstan-ignore instanceof.alwaysTrue (TransientToken pode ser retornado em contexto de teste)
        if (! $currentToken instanceof PersonalAccessToken) {
            throw new AccessDeniedHttpException('Token de refresh inválido.');
        }

        $tokenName  = $currentToken->name; // "{device}:refresh"
        $deviceName = str_replace(':refresh', '', $tokenName);

        // Criar novo access token
        $accessToken = $user->createToken(
            name: "{$deviceName}:access",
            abilities: ['access'],
            expiresAt: CarbonImmutable::now()->addHours(CreateTokenPairAction::ACCESS_EXPIRATION_HOURS)
        );

        return [
            'access_token' => $accessToken->plainTextToken,
            'expires_in'   => CreateTokenPairAction::ACCESS_EXPIRATION_HOURS * 3600,
        ];
    }
}
