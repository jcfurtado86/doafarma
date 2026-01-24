<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationAppointment;

use App\Actions\MedicationAppointment\ListReceptorHistoryAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MedicationAppointmentResource;
use App\Models\MedicationAppointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HistoryController extends Controller
{
    /**
     * Handle the incoming request to list receptor's medication history.
     */
    public function __invoke(
        Request $request,
        ListReceptorHistoryAction $action
    ): AnonymousResourceCollection {
        $this->authorize('viewOwn', MedicationAppointment::class);

        $appointments = $action->execute($request->user());

        return MedicationAppointmentResource::collection($appointments);
    }
}
