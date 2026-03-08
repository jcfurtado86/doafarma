<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CreateDoctorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DoctorRegistrationRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class DoctorRegistrationController extends Controller
{
    public function __construct(
        private readonly CreateDoctorAction $createDoctorAction
    ) {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(DoctorRegistrationRequest $request): JsonResponse
    {
        $user = $this->createDoctorAction->execute([
            'name'           => $request->validated('name'),
            'email'          => $request->validated('email'),
            'phone_number'   => $request->validated('phone_number'),
            'crm'            => $request->validated('crm'),
            'crm_uf'         => $request->validated('crm_uf'),
            'password'       => $request->validated('password'),
            'terms_accepted' => $request->validated('terms_accepted'),
            'addresses'      => $request->validated('addresses'),
        ]);

        $user->load('doctor');

        $token = $user->createToken($request->validated('device_name'))->plainTextToken;

        return response()->json([
            'data' => [
                'user'  => new UserResource($user),
                'token' => $token,
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}
