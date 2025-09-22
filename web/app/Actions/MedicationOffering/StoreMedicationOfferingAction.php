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

        return $doctor->medicationOfferings()->create($data);
    }
}
