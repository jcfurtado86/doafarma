<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\TokenAbility;
use App\Models\User;

class LogoutAction
{
    /**
     * Revoke all tokens for the current device.
     */
    public function execute(User $user): void
    {
        $currentToken = $user->currentAccessToken();
        $pattern      = '/:(' . TokenAbility::Access->value . '|' . TokenAbility::Refresh->value . ')$/';
        $devicePrefix = preg_replace($pattern, '', $currentToken->name);

        $user->tokens()
            ->where('name', 'like', "{$devicePrefix}:%")
            ->delete();
    }
}
