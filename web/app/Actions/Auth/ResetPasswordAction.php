<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordAction
{
    /**
     * Execute the password reset action.
     *
     * @param  ResetPasswordRequest  $request
     * @return string The password broker status
     */
    public function execute(ResetPasswordRequest $request): string
    {
        /** @var string $status */
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request): void {
                $user->forceFill([
                    'password'       => Hash::make($request->string('password')->toString()),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        return $status;
    }
}
