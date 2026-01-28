<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\TokenAbility;
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

        // @phpstan-ignore instanceof.alwaysTrue (actingAs() in tests returns TransientToken)
        if (! $currentToken instanceof PersonalAccessToken) {
            throw new AuthenticationException('Token não encontrado.');
        }

        $pattern      = '/:(' . TokenAbility::Access->value . '|' . TokenAbility::Refresh->value . ')$/';
        $devicePrefix = preg_replace($pattern, '', $currentToken->name);

        $user->tokens()
            ->where('name', 'like', "{$devicePrefix}:%")
            ->delete();
    }
}
