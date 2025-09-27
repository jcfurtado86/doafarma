<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Http\Requests\Api\V1\Auth\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginAction
{
    /**
     * Execute the login action.
     *
     * @return array<string, mixed>
     */
    public function execute(LoginRequest $request): array
    {
        $request->authenticate();

        $user  = Auth::user();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }
}
