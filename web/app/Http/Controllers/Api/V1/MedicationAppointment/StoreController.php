<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationAppointment;

use App\Actions\MedicationAppointment\CreateMedicationAppointmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicationAppointment\StoreMedicationAppointmentRequest;
use App\Http\Resources\Api\V1\MedicationAppointmentResource;
use App\Models\MedicationAppointment;
use App\Models\MedicationRequest;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        StoreMedicationAppointmentRequest $request,
        CreateMedicationAppointmentAction $action
    ): MedicationAppointmentResource | JsonResponse {
        $medicationRequest = MedicationRequest::findOrFail(
            (int) $request->validated('medication_request_id')
        );

        // Check if request is confirmed
        if ($medicationRequest->status !== 'confirmed') {
            return response()->json([
                'message' => 'Apenas solicitações confirmadas podem ter agendamento.',
            ], 422);
        }

        // Check if appointment already exists
        $existingAppointment = MedicationAppointment::where(
            'medication_request_id',
            $medicationRequest->id
        )->first();

        if ($existingAppointment !== null) {
            return response()->json([
                'message' => 'Já existe um agendamento para esta solicitação.',
            ], 409);
        }

        try {
            $appointment = $action->execute(
                $medicationRequest,
                $request->validated('scheduled_date'),
                $request->validated('scheduled_time')
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return MedicationAppointmentResource::make($appointment)
            ->response()
            ->setStatusCode(201);
    }
}
