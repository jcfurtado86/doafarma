<?php

declare(strict_types = 1);

namespace App\Actions\MedicationOffering;

use App\Models\MedicationOffering;

class DeleteMedicationOfferingAction
{
    /**
     * Execute the action.
     *
     * @param MedicationOffering $medicationOffering the medication offering to delete
     */
    public function execute(MedicationOffering $medicationOffering): void
    {
        $medicationOffering->delete();
    }
}
