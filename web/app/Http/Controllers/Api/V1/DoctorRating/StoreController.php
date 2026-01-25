<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\DoctorRating;

use App\Actions\DoctorRating\CreateRatingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DoctorRating\StoreRatingRequest;
use App\Http\Resources\Api\V1\DoctorRatingResource;
use App\Models\DoctorRating;
use App\Models\MedicationAppointment;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        MedicationAppointment $medicationAppointment,
        StoreRatingRequest $request,
        CreateRatingAction $action
    ): DoctorRatingResource | JsonResponse {
        // Check if appointment is completed
        if ($medicationAppointment->status !== 'completed') {
            return response()->json([
                'message' => 'Apenas entregas concluídas podem ser avaliadas.',
            ], 422);
        }

        // Check if rating already exists
        $existingRating = DoctorRating::where(
            'medication_appointment_id',
            $medicationAppointment->id
        )->first();

        if ($existingRating !== null) {
            return response()->json([
                'message' => 'Você já avaliou esta entrega.',
            ], 409);
        }

        $rating = $action->execute(
            $medicationAppointment,
            $request->user(),
            $request->validated()
        );

        return DoctorRatingResource::make($rating)
            ->response()
            ->setStatusCode(201);
    }
}
