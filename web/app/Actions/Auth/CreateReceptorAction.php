<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Action to create a new receptor (patient) user.
 *
 * This action handles the creation of a receptor user with proper
 * hashing of password and atomic database operations.
 */
class CreateReceptorAction
{
    /**
     * Execute the action.
     *
     * @param  array{
     *     name: string,
     *     email: string,
     *     cpf: string,
     *     phone_number: string,
     *     password: string,
     *     terms_accepted: bool
     * }  $data
     */
    public function execute(array $data): User
    {
        return DB::transaction(fn (): User => User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'cpf'               => $data['cpf'],
            'role'              => 'receptor',
            'phone_number'      => $data['phone_number'],
            'password'          => Hash::make($data['password']),
            'terms_accepted'    => $data['terms_accepted'],
            'terms_accepted_at' => now(),
        ]));
    }
}
