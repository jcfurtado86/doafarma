<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationAppointment;

use App\Actions\MedicationAppointment\ConfirmDeliveryDoctorAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MedicationAppointmentResource;
use App\Models\MedicationAppointment;
use Illuminate\Http\JsonResponse;

class ConfirmDeliveryDoctorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        MedicationAppointment $medicationAppointment,
        ConfirmDeliveryDoctorAction $action
    ): MedicationAppointmentResource | JsonResponse {
        $this->authorize('confirmDeliveryDoctor', $medicationAppointment);

        // Check if already completed
        if ($medicationAppointment->status === 'completed') {
            return response()->json([
                'message' => 'Este agendamento já foi concluído.',
            ], 422);
        }

        // Check if already confirmed by doctor
        if ($medicationAppointment->doctor_confirmed) {
            return response()->json([
                'message' => 'Você já confirmou a entrega.',
            ], 422);
        }

        // Check if scheduled date has passed
        if ($medicationAppointment->scheduled_date->isFuture()) {
            return response()->json([
                'message' => 'A entrega só pode ser confirmada a partir da data agendada.',
            ], 422);
        }

        $appointment = $action->execute($medicationAppointment);

        return MedicationAppointmentResource::make($appointment);
    }
}
