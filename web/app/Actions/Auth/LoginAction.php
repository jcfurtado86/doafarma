<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Http\Requests\Api\V1\Auth\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginAction
{
    public function __construct(
        private CreateTokenPairAction $createTokenPair
    ) {
    }

    /**
     * Execute the login action.
     *
     * @return array<string, mixed>
     */
    public function execute(LoginRequest $request): array
    {
        $request->authenticate();

        $user       = Auth::user();
        $deviceName = $request->input('device_name', 'mobile-app');

        $tokens = $this->createTokenPair->execute($user, $deviceName);

        return [
            'user' => $user,
            ...$tokens,
        ];
    }
}
