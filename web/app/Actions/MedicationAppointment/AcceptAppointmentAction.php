<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\MedicationAppointment;

class AcceptAppointmentAction
{
    /**
     * Execute the action to accept an appointment proposal.
     */
    public function execute(MedicationAppointment $appointment): MedicationAppointment
    {
        $appointment->update(['status' => 'confirmed']);

        return $appointment->fresh([
            'medicationRequest.medicationOffering.drug',
            'medicationRequest.medicationOffering.doctor.user',
            'medicationRequest.receptor',
            'address',
        ]);
    }
}
