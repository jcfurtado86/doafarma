<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationAppointment;

use App\Actions\MedicationAppointment\AcceptAppointmentAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MedicationAppointmentResource;
use App\Models\MedicationAppointment;
use Illuminate\Http\JsonResponse;

class AcceptController extends Controller
{
    /**
     * Handle the incoming request to accept an appointment proposal.
     */
    public function __invoke(
        MedicationAppointment $medicationAppointment,
        AcceptAppointmentAction $action
    ): MedicationAppointmentResource | JsonResponse {
        $this->authorize('accept', $medicationAppointment);

        if ($medicationAppointment->status !== 'proposed') {
            return response()->json([
                'message' => 'Apenas agendamentos pendentes podem ser aceitos.',
            ], 422);
        }

        $appointment = $action->execute($medicationAppointment);

        return MedicationAppointmentResource::make($appointment);
    }
}
