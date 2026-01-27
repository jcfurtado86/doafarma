<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\Address;
use App\Models\MedicationAppointment;
use App\Models\MedicationRequest;
use RuntimeException;

class CreateMedicationAppointmentAction
{
    /**
     * Execute the action to create a medication appointment.
     */
    public function execute(
        MedicationRequest $request,
        string $scheduledDate,
        string $scheduledTime
    ): MedicationAppointment {
        $doctorUserId = $request->medicationOffering->doctor->user_id;

        $address = Address::where('user_id', $doctorUserId)->first();

        if ($address === null) {
            throw new RuntimeException('O médico não possui endereços cadastrados.');
        }

        $appointment = MedicationAppointment::create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
            'scheduled_date'        => $scheduledDate,
            'scheduled_time'        => $scheduledTime,
            'status'                => 'proposed',
            'proposed_by'           => 'receptor',
        ]);

        return $appointment->load([
            'medicationRequest.medicationOffering.drug',
            'medicationRequest.medicationOffering.doctor.user',
            'medicationRequest.receptor',
            'address',
        ]);
    }
}
