<?php

declare(strict_types = 1);

namespace App\Policies;

use App\Models\MedicationRequest;
use App\Models\User;

class MedicationRequestPolicy
{
    /**
     * Determine whether the user can create medication requests.
     * Only receptors can create requests.
     */
    public function create(User $user): bool
    {
        return $user->role === 'receptor';
    }

    /**
     * Determine whether the user can view their own requests.
     * Only receptors can list their requests.
     */
    public function viewOwn(User $user): bool
    {
        return $user->role === 'receptor';
    }

    /**
     * Determine whether the user can view received requests.
     * Only doctors can view requests for their offerings.
     */
    public function viewReceived(User $user): bool
    {
        return $user->role === 'doctor' && $user->doctor !== null;
    }

    /**
     * Determine whether the user can confirm the request.
     * Only the doctor who owns the offering can confirm requests.
     * Status check is handled in the controller for proper error message.
     */
    public function confirm(User $user, MedicationRequest $medicationRequest): bool
    {
        if ($user->doctor === null) {
            return false;
        }

        return $user->doctor->id === $medicationRequest->medicationOffering->doctor_id;
    }

    /**
     * Determine whether the user can reject the request.
     * Only the doctor who owns the offering can reject requests.
     * Status check is handled in the controller for proper error message.
     */
    public function reject(User $user, MedicationRequest $medicationRequest): bool
    {
        if ($user->doctor === null) {
            return false;
        }

        return $user->doctor->id === $medicationRequest->medicationOffering->doctor_id;
    }
}
