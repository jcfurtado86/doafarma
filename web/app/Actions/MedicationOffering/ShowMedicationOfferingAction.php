<?php

declare(strict_types = 1);

namespace App\Actions\MedicationOffering;

use App\Models\MedicationOffering;

class ShowMedicationOfferingAction
{
    /**
     * Execute the action.
     */
    public function execute(MedicationOffering $medicationOffering): MedicationOffering
    {
        $medicationOffering->load(['drug', 'doctor']);

        return $medicationOffering;
    }
}
