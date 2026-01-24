<?php

declare(strict_types = 1);

namespace App\Actions\PushToken;

use App\Http\Requests\Api\V1\PushToken\RegisterPushTokenRequest;
use App\Models\PushToken;

class RegisterPushTokenAction
{
    /**
     * Execute the action to register or update a push token.
     */
    public function execute(RegisterPushTokenRequest $request): PushToken
    {
        $user = $request->user();

        return PushToken::updateOrCreate(
            ['token' => $request->input('token')],
            [
                'user_id'     => $user->id,
                'device_type' => $request->input('device_type'),
            ]
        );
    }
}
