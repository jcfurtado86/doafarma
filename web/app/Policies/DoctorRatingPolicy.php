<?php

declare(strict_types = 1);

namespace App\Policies;

use App\Models\DoctorRating;
use App\Models\MedicationAppointment;
use App\Models\User;

class DoctorRatingPolicy
{
    /**
     * Determine whether the user can create a rating.
     * Only receptors can create ratings for their own completed appointments.
     * Note: Status check (completed) and duplicate check are done in the controller.
     */
    public function create(User $user, MedicationAppointment $appointment): bool
    {
        return $user->role === 'receptor'
            && $appointment->medicationRequest->receptor_id === $user->id;
    }

    /**
     * Determine whether the user can view ratings for a doctor.
     * Anyone authenticated can view ratings.
     */
    public function viewDoctorRatings(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update their own rating.
     */
    public function update(User $user, DoctorRating $rating): bool
    {
        return $user->role === 'receptor'
            && $rating->receptor_id === $user->id;
    }
}
