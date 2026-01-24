<?php

declare(strict_types = 1);

namespace App\Actions\MedicationRequest;

use App\Jobs\NotifyDoctorNewRequestJob;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CreateMedicationRequestAction
{
    /**
     * Execute the action to create a medication request.
     *
     * @param int  $medicationOfferingId The ID of the medication offering to request
     * @param User $receptor             The user making the request (receptor)
     *
     * @throws ConflictHttpException When the offering is already reserved
     */
    public function execute(int $medicationOfferingId, User $receptor): MedicationRequest
    {
        $request = DB::transaction(function () use ($medicationOfferingId, $receptor): MedicationRequest {
            // Lock the offering row to prevent race conditions
            $offering = MedicationOffering::lockForUpdate()->findOrFail($medicationOfferingId);

            // Check if offering is available
            if ($offering->status !== 'available') {
                throw new ConflictHttpException('Esta oferta já foi reservada por outro usuário.');
            }

            // Create the request
            $medicationRequest = MedicationRequest::create([
                'receptor_id'            => $receptor->id,
                'medication_offering_id' => $offering->id,
                'status'                 => 'pending',
            ]);

            // Update offering status to reserved
            $offering->update(['status' => 'reserved']);

            // Load relationships for the response
            $medicationRequest->load(['medicationOffering.drug', 'medicationOffering.doctor.user', 'receptor']);

            return $medicationRequest;
        });

        // Dispatch notification job after transaction commits
        NotifyDoctorNewRequestJob::dispatch($request->id);

        return $request;
    }
}
