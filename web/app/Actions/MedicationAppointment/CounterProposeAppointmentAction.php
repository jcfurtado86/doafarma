<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\MedicationAppointment;

class CounterProposeAppointmentAction
{
    /**
     * Execute the action to counter-propose an appointment.
     */
    public function execute(
        MedicationAppointment $appointment,
        string $scheduledDate,
        string $scheduledTime,
        string $proposedBy,
        ?int $addressId = null
    ): MedicationAppointment {
        $updateData = [
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'proposed_by'    => $proposedBy,
        ];

        if ($addressId !== null) {
            $updateData['address_id'] = $addressId;
        }

        $appointment->update($updateData);

        return $appointment->fresh([
            'medicationRequest.medicationOffering.drug',
            'medicationRequest.medicationOffering.doctor.user',
            'medicationRequest.receptor',
            'address',
        ]);
    }
}
