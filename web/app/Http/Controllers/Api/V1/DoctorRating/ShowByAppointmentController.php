<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\DoctorRating;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DoctorRatingResource;
use App\Models\DoctorRating;
use App\Models\MedicationAppointment;
use Illuminate\Http\JsonResponse;

class ShowByAppointmentController extends Controller
{
    /**
     * Get the rating for a specific appointment (if exists).
     */
    public function __invoke(MedicationAppointment $medicationAppointment): DoctorRatingResource | JsonResponse
    {
        $this->authorize('showByAppointment', [DoctorRating::class, $medicationAppointment]);

        $rating = DoctorRating::where('medication_appointment_id', $medicationAppointment->id)
            ->with(['doctor.user', 'receptor'])
            ->first();

        if ($rating === null) {
            return response()->json([
                'data' => null,
            ]);
        }

        return DoctorRatingResource::make($rating);
    }
}
