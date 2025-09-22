<?php

declare(strict_types = 1);

namespace App\Actions\MedicationOffering;

use App\Models\MedicationOffering;
use App\Models\User;

class StoreMedicationOfferingAction
{
    /**
     * Execute the action.
     *
     * @param array<string, mixed> $data validated payload
     * @param User  $user authenticated user (doctor)
     */
    public function execute(array $data, User $user): MedicationOffering
    {
        $doctor = $user->doctor;

        return $doctor->medicationOfferings()->create([
            'user_id'    => $user->id,
            'drug_id'    => (int) $data['drug_id'],
            'lot_number' => trim((string) $data['lot_number']),
            'expires_at' => $data['expires_at'],
            'quantity'   => (int) $data['quantity'],
        ]);
    }
}
