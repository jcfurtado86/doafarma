<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationAppointment;

use App\Actions\MedicationAppointment\ListDoctorAppointmentsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MedicationAppointmentResource;
use App\Models\MedicationAppointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReceivedController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        ListDoctorAppointmentsAction $action
    ): AnonymousResourceCollection {
        $this->authorize('viewReceived', MedicationAppointment::class);

        $status = $request->query('status');

        $appointments = $action->execute(
            $request->user()->doctor,
            is_string($status) ? $status : null
        );

        return MedicationAppointmentResource::collection($appointments);
    }
}
