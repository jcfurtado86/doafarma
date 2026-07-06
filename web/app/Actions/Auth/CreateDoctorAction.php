<?php

declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\CrmValidationService;
use App\Values\CrmValidationResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Action to create a new doctor user.
 *
 * This action validates the CRM via ConsultaCRM API,
 * determines user status, and creates user with doctor
 * profile and addresses in a database transaction.
 */
class CreateDoctorAction
{
    public function __construct(
        private readonly CrmValidationService $crmValidationService
    ) {
    }

    /**
     * Execute the action.
     *
     * @param  array{
     *     name: string,
     *     email: string,
     *     phone_number: string,
     *     crm: string,
     *     crm_uf: string,
     *     password: string,
     *     terms_accepted: bool,
     *     addresses: array<int, array<string, mixed>>
     * }  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): User
    {
        $crmResult = $this->crmValidationService->validate($data['crm'], $data['crm_uf']);

        if ($crmResult->isRejected()) {
            throw ValidationException::withMessages([
                'crm' => ["O CRM informado está com situação '{$crmResult->status->value}' no conselho de medicina. Não é possível prosseguir com o cadastro."],
            ]);
        }

        $status = $this->determineUserStatus($crmResult);

        return DB::transaction(function () use ($data, $crmResult, $status): User {
            $user = User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'role'              => 'doctor',
                'phone_number'      => $data['phone_number'],
                'password'          => Hash::make($data['password']),
                'status'            => $status,
                'terms_accepted'    => $data['terms_accepted'],
                'terms_accepted_at' => now(),
            ]);

            $user->doctor()->create([
                'crm'             => $data['crm'],
                'crm_uf'          => $data['crm_uf'],
                'crm_verified_at' => $crmResult->isApproved() ? now() : null,
                'crm_source'      => $crmResult->source,
                'specialty'       => $crmResult->specialty,
            ]);

            $user->addresses()->createMany($data['addresses']);

            return $user;
        });
    }

    private function determineUserStatus(CrmValidationResult $result): UserStatus
    {
        return $result->isApproved() ? UserStatus::Approved : UserStatus::Pending;
    }
}
