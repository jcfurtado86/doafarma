<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use Illuminate\Support\Facades\Password;

class SendPasswordResetAction
{
    /**
     * Send a password reset link to the given email.
     */
    public function execute(ForgotPasswordRequest $request): void
    {
        Password::sendResetLink($request->only('email'));
    }
}
