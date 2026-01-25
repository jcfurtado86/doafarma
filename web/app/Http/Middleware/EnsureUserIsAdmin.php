<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== UserRole::Admin) {
            return response()->json([
                'message' => 'Acesso não autorizado. Apenas administradores podem acessar este recurso.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
