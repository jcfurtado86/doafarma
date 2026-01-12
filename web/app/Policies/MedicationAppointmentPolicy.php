<?php

declare(strict_types = 1);

namespace App\Policies;

use App\Models\MedicationAppointment;
use App\Models\MedicationRequest;
use App\Models\User;

class MedicationAppointmentPolicy
{
    /**
     * Determine whether the receptor can create an appointment.
     * Only receptors can create appointments for their own requests.
     * Note: Status check (confirmed) is done in the controller as a business rule.
     */
    public function create(User $user, MedicationRequest $request): bool
    {
        return $user->role === 'receptor'
            && $request->receptor_id === $user->id;
    }

    /**
     * Determine whether the user can view their own appointments.
     * Only receptors can list their appointments.
     */
    public function viewOwn(User $user): bool
    {
        return $user->role === 'receptor';
    }

    /**
     * Determine whether the user can view received appointments.
     * Only doctors can view appointments for their offerings.
     */
    public function viewReceived(User $user): bool
    {
        return $user->role === 'doctor' && $user->doctor !== null;
    }

    /**
     * Determine whether the receptor can confirm delivery.
     * Note: Status checks (completed, already confirmed) are done in the controller as business rules.
     */
    public function confirmDeliveryReceptor(User $user, MedicationAppointment $appointment): bool
    {
        return $user->role === 'receptor'
            && $appointment->medicationRequest->receptor_id === $user->id;
    }

    /**
     * Determine whether the doctor can confirm delivery.
     * Note: Status checks (completed, already confirmed) are done in the controller as business rules.
     */
    public function confirmDeliveryDoctor(User $user, MedicationAppointment $appointment): bool
    {
        if ($user->doctor === null) {
            return false;
        }

        return $appointment->medicationRequest->medicationOffering->doctor_id === $user->doctor->id;
    }
}
