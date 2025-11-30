<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ApiLoginController extends Controller
{
    public function __construct(
        private readonly AuthenticateUserAction $authenticateUserAction
    ) {
    }

    /**
     * Handle an API login request.
     *
     * @throws ValidationException
     */
    public function __invoke(ApiLoginRequest $request): JsonResponse
    {
        // Check rate limiting
        $request->ensureIsNotRateLimited();

        try {
            $user = $this->authenticateUserAction->execute([
                'email'    => $request->validated('email'),
                'password' => $request->validated('password'),
            ]);

            // Clear rate limiter on success
            $request->clearRateLimiter();

            // Create Sanctum token
            $token = $user->createToken($request->validated('device_name'))->plainTextToken;

            return response()->json([
                'data' => [
                    'token' => $token,
                    'user'  => new UserResource($user),
                ],
            ]);
        } catch (ValidationException $e) {
            // Increment rate limiter on failure
            $request->hitRateLimiter();

            throw $e;
        }
    }
}
