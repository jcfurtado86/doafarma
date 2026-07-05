<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CreateReceptorAction;
use App\Actions\Auth\CreateTokenPairAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ReceptorRegistrationRequest;
use App\Http\Resources\Api\V1\RegistrationResource;
use Illuminate\Http\JsonResponse;

class ReceptorRegistrationController extends Controller
{
    public function __construct(
        private readonly CreateReceptorAction $createReceptorAction
    ) {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(ReceptorRegistrationRequest $request, CreateTokenPairAction $createTokenPairAction): JsonResponse
    {
        $user = $this->createReceptorAction->execute([
            'name'           => $request->validated('name'),
            'email'          => $request->validated('email'),
            'cpf'            => $request->validated('cpf'),
            'phone_number'   => $request->validated('phone_number'),
            'password'       => $request->validated('password'),
            'terms_accepted' => $request->validated('terms_accepted'),
        ]);

        $tokens = $createTokenPairAction->execute($user, $request->validated('device_name'));

        return new RegistrationResource(array_merge(['user' => $user], $tokens))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}
