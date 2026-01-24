<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\PushToken;

use App\Actions\PushToken\RegisterPushTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PushToken\RegisterPushTokenRequest;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request to register a push token.
     */
    public function __invoke(
        RegisterPushTokenRequest $request,
        RegisterPushTokenAction $action,
    ): JsonResponse {
        $action->execute($request);

        return response()->json([
            'message' => 'Token registrado com sucesso.',
        ]);
    }
}
