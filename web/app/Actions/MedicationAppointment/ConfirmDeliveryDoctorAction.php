<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\MedicationAppointment;
use Illuminate\Support\Facades\DB;

class ConfirmDeliveryDoctorAction
{
    /**
     * Execute the action to confirm delivery by doctor.
     */
    public function execute(MedicationAppointment $appointment): MedicationAppointment
    {
        return DB::transaction(function () use ($appointment): MedicationAppointment {
            $appointment->update(['doctor_confirmed' => true]);

            // If both parties confirmed, complete the appointment and offering
            if ($appointment->receptor_confirmed) {
                $appointment->update(['status' => 'completed']);
                $appointment->medicationRequest->medicationOffering->update([
                    'status' => 'completed',
                ]);
            }

            return $appointment->fresh([
                'medicationRequest.medicationOffering.drug',
                'medicationRequest.medicationOffering.doctor.user',
                'medicationRequest.receptor',
                'address',
            ]);
        });
    }
}
