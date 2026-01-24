<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\PushToken;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteController extends Controller
{
    /**
     * Handle the incoming request to delete a push token (logout).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->input('token');

        if ($token) {
            PushToken::where('user_id', $request->user()->id)
                ->where('token', $token)
                ->delete();
        }

        return response()->json([
            'message' => 'Token removido com sucesso.',
        ]);
    }
}
