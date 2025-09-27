<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\LoginResource;

class LoginController extends Controller
{
    /**
     * Handle the incoming login request.
     */
    public function __invoke(LoginRequest $request, LoginAction $action): LoginResource
    {
        $result = $action->execute($request);

        return new LoginResource($result);
    }
}
