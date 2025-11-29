<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CreateReceptorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ReceptorRegistrationRequest;
use App\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

class ReceptorRegistrationController extends Controller
{
    public function __construct(
        private readonly CreateReceptorAction $createReceptorAction
    ) {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(ReceptorRegistrationRequest $request): Response
    {
        $user = $this->createReceptorAction->execute([
            'name'           => $request->validated('name'),
            'email'          => $request->validated('email'),
            'cpf'            => $request->validated('cpf'),
            'phone_number'   => $request->validated('phone_number'),
            'password'       => $request->validated('password'),
            'terms_accepted' => $request->validated('terms_accepted'),
        ]);

        $token = $user->createToken($request->validated('device_name'))->plainTextToken;

        return response()->json([
            'data' => [
                'user'  => new UserResource($user),
                'token' => $token,
            ],
        ], Response::HTTP_CREATED);
    }
}
