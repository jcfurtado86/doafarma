<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Action to authenticate a user via API.
 *
 * This action handles user authentication, returning the user
 * if credentials are valid, or throwing an exception if not.
 */
class AuthenticateUserAction
{
    /**
     * Execute the authentication action.
     *
     * @param  array{email: string, password: string}  $credentials
     *
     * @throws ValidationException
     */
    public function execute(array $credentials): User
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        return $user;
    }
}
