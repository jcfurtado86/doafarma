<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LogoutAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController
{
    public function __invoke(Request $request, LogoutAction $action): JsonResponse
    {
        $action->execute($request->user());

        return response()->json([
            'message' => 'Logout realizado com sucesso',
        ]);
    }
}
