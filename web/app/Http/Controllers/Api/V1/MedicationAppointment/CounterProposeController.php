<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationAppointment;

use App\Actions\MedicationAppointment\CounterProposeAppointmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicationAppointment\CounterProposeAppointmentRequest;
use App\Http\Resources\Api\V1\MedicationAppointmentResource;
use App\Models\Address;
use App\Models\MedicationAppointment;
use Illuminate\Http\JsonResponse;

class CounterProposeController extends Controller
{
    /**
     * Handle the incoming request to counter-propose an appointment.
     */
    public function __invoke(
        CounterProposeAppointmentRequest $request,
        MedicationAppointment $medicationAppointment,
        CounterProposeAppointmentAction $action
    ): MedicationAppointmentResource | JsonResponse {
        $this->authorize('counterPropose', $medicationAppointment);

        if ($medicationAppointment->status !== 'proposed') {
            return response()->json([
                'message' => 'Apenas agendamentos pendentes podem ser reagendados.',
            ], 422);
        }

        $user      = $request->user();
        $addressId = $request->validated('address_id');

        // If doctor is counter-proposing and provided an address, validate ownership
        if ($user->role === 'doctor' && $addressId !== null) {
            $address = Address::find($addressId);

            if ($address === null || $address->user_id !== $user->id) {
                return response()->json([
                    'message' => 'O endereço selecionado não pertence a você.',
                ], 422);
            }
        }

        // Receptors cannot change the address
        if ($user->role === 'receptor') {
            $addressId = null;
        }

        $proposedBy = $user->role === 'doctor' ? 'doctor' : 'receptor';

        $appointment = $action->execute(
            $medicationAppointment,
            $request->validated('scheduled_date'),
            $request->validated('scheduled_time'),
            $proposedBy,
            $addressId
        );

        return MedicationAppointmentResource::make($appointment);
    }
}
