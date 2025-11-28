<?php

declare(strict_types = 1);

namespace App\Actions\MedicationOffering;

use App\Models\MedicationOffering;

class UpdateMedicationOfferingAction
{
    /**
     * Execute the action.
     *
     * @param MedicationOffering $medicationOffering the medication offering to update
     * @param array<string, mixed> $data validated payload
     */
    public function execute(MedicationOffering $medicationOffering, array $data): MedicationOffering
    {
        $medicationOffering->update($data);

        return $medicationOffering->fresh();
    }
}
