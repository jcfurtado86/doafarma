<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\MedicationAppointment;
use Illuminate\Support\Facades\DB;

class ConfirmDeliveryReceptorAction
{
    /**
     * Execute the action to confirm delivery by receptor.
     */
    public function execute(MedicationAppointment $appointment): MedicationAppointment
    {
        return DB::transaction(function () use ($appointment): MedicationAppointment {
            $appointment->update(['receptor_confirmed' => true]);

            // If both parties confirmed, complete the appointment and offering
            if ($appointment->doctor_confirmed) {
                $appointment->update(['status' => 'completed']);
                $appointment->medicationRequest->medicationOffering->update([
                    'status' => 'completed',
                ]);
            }

            return $appointment->fresh(MedicationAppointment::RELATIONS_FULL);
        });
    }
}
