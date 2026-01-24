<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationAppointment;

use App\Actions\MedicationAppointment\ListDoctorDonationHistoryAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MedicationAppointmentResource;
use App\Models\MedicationAppointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorHistoryController extends Controller
{
    /**
     * Handle the incoming request to list doctor's donation history.
     */
    public function __invoke(
        Request $request,
        ListDoctorDonationHistoryAction $action
    ): AnonymousResourceCollection {
        $this->authorize('viewDonationHistory', MedicationAppointment::class);

        $appointments = $action->execute($request->user()->doctor);

        return MedicationAppointmentResource::collection($appointments);
    }
}
