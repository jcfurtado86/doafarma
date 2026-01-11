<?php

declare(strict_types = 1);

namespace App\Actions\MedicationRequest;

use App\Models\MedicationRequest;

class ConfirmMedicationRequestAction
{
    /**
     * Execute the action to confirm a medication request.
     *
     * @param MedicationRequest $request The request to confirm
     */
    public function execute(MedicationRequest $request): MedicationRequest
    {
        $request->update(['status' => 'confirmed']);

        // Offering remains reserved
        return $request->fresh(['medicationOffering.drug', 'receptor']);
    }
}
