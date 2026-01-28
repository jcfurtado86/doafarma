<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutAction
{
    /**
     * Revoke all tokens for the current device.
     *
     * @throws AuthenticationException
     */
    public function execute(User $user): void
    {
        $currentToken = $user->currentAccessToken();

        // @phpstan-ignore instanceof.alwaysTrue (TransientToken pode ser retornado em contexto de teste)
        if (! $currentToken instanceof PersonalAccessToken) {
            throw new AuthenticationException('Token não encontrado.');
        }

        $tokenName = $currentToken->name;

        // Extrair device prefix (ex: "iPhone 15" de "iPhone 15:access")
        $devicePrefix = preg_replace('/:(access|refresh)$/', '', $tokenName);

        // Revogar todos tokens deste device
        $user->tokens()
            ->where('name', 'like', "{$devicePrefix}:%")
            ->delete();
    }
}
