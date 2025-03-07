<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DoctorRegistrationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class DoctorRegistrationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(DoctorRegistrationRequest $request): Response
    {
        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->string('password')->toString()),
            'phone_number'      => $request->phone_number,
            'terms_accepted'    => $request->terms_accepted,
            'terms_accepted_at' => now(),
        ]);

        $user->doctor()->create([
            'crm'    => $request->crm,
            'crm_uf' => $request->crm_uf,
        ]);

        $user->addresses()->createMany($request->addresses);

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent(Response::HTTP_CREATED);
    }
}
