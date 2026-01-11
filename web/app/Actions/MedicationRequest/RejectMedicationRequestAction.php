<?php

declare(strict_types = 1);

namespace App\Actions\MedicationRequest;

use App\Models\MedicationRequest;
use Illuminate\Support\Facades\DB;

class RejectMedicationRequestAction
{
    /**
     * Execute the action to reject a medication request.
     *
     * @param MedicationRequest $request The request to reject
     */
    public function execute(MedicationRequest $request): MedicationRequest
    {
        DB::transaction(function () use ($request): void {
            $request->update(['status' => 'rejected']);

            // Return offering to available status
            $request->medicationOffering->update(['status' => 'available']);
        });

        return $request->fresh(['medicationOffering.drug', 'receptor']);
    }
}
