<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RefreshTokenAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefreshController
{
    public function __invoke(Request $request, RefreshTokenAction $action): JsonResponse
    {
        $result = $action->execute($request->user());

        return response()->json([
            'data'    => $result,
            'message' => 'Token renovado com sucesso',
        ]);
    }
}
