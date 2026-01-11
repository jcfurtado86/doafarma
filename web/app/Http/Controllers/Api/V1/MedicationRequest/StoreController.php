<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationRequest;

use App\Actions\MedicationRequest\CreateMedicationRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicationRequest\StoreMedicationRequestRequest;
use App\Http\Resources\Api\V1\MedicationRequestResource;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        StoreMedicationRequestRequest $request,
        CreateMedicationRequestAction $action
    ): MedicationRequestResource | JsonResponse {
        $medicationRequest = $action->execute(
            (int) $request->validated('medication_offering_id'),
            $request->user()
        );

        return MedicationRequestResource::make($medicationRequest)
            ->response()
            ->setStatusCode(201);
    }
}
