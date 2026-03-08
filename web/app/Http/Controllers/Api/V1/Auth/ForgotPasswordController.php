<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\SendPasswordResetAction;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController
{
    public function __invoke(ForgotPasswordRequest $request, SendPasswordResetAction $action): JsonResponse
    {
        $action->execute($request);

        return response()->json([
            'message' => 'If an account with that email exists, we have sent a password reset link.',
        ]);
    }
}
