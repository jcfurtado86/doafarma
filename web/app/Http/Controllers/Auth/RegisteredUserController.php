<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\Address;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterUserRequest $request): Response
    {
        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->string('password')->toString()),
            'phone_number'      => $request->phone_number,
            'crm'               => $request->crm,
            'crm_uf'            => $request->crm_uf,
            'terms_accepted'    => $request->terms_accepted,
            'terms_accepted_at' => now(),
        ]);

        foreach ((array)($request->addresses ?? []) as $address) {
            if (! is_array($address)) {
                continue;
            }
            Address::create([
                'user_id'       => $user->id,
                'location_name' => $address['location_name'] ?? '',
                'full_address'  => $address['full_address'] ?? '',
                'complement'    => $address['complement'] ?? null,
                'cep'           => $address['cep'] ?? '',
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent(Response::HTTP_CREATED);
    }
}
