<?php

declare(strict_types = 1);

namespace App\Actions\DoctorRating;

use App\Models\DoctorRating;
use App\Models\MedicationAppointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRatingAction
{
    /**
     * Execute the action to create a doctor rating.
     *
     * @param array{rating: int, comment: string|null} $data
     */
    public function execute(MedicationAppointment $appointment, User $receptor, array $data): DoctorRating
    {
        return DB::transaction(function () use ($appointment, $receptor, $data): DoctorRating {
            $doctorId = $appointment->medicationRequest->medicationOffering->doctor_id;

            $rating = DoctorRating::create([
                'medication_appointment_id' => $appointment->id,
                'doctor_id'                 => $doctorId,
                'receptor_id'               => $receptor->id,
                'rating'                    => $data['rating'],
                'comment'                   => $data['comment'] ?? null,
            ]);

            return $rating->load([
                'medicationAppointment.medicationRequest.medicationOffering.drug',
                'doctor.user',
                'receptor',
            ]);
        });
    }
}
