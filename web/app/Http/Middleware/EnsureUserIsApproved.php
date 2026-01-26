<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return match ($user->status) {
            UserStatus::Approved => $next($request),
            UserStatus::Pending  => response()->json([
                'message' => 'Seu cadastro está aguardando aprovação.',
                'status'  => 'pending',
            ], Response::HTTP_FORBIDDEN),
            UserStatus::Rejected => response()->json([
                'message' => 'Seu cadastro foi rejeitado. Entre em contato com o suporte.',
                'status'  => 'rejected',
            ], Response::HTTP_FORBIDDEN),
        };
    }
}
