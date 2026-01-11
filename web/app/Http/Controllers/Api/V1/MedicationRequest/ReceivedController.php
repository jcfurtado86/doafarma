<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationRequest;

use App\Actions\MedicationRequest\ListDoctorRequestsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MedicationRequestResource;
use App\Models\MedicationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReceivedController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        ListDoctorRequestsAction $action
    ): AnonymousResourceCollection {
        $this->authorize('viewReceived', MedicationRequest::class);

        $status      = $request->query('status');
        $statusValue = is_string($status) ? $status : null;

        $requests = $action->execute($request->user(), $statusValue);

        return MedicationRequestResource::collection($requests);
    }
}
