<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationOffering;

use App\Actions\MedicationOffering\StoreMedicationOfferingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicationOffering\StoreMedicationOfferingRequest;
use App\Http\Resources\Api\V1\MedicationOfferingResource;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreMedicationOfferingRequest $request, StoreMedicationOfferingAction $action): MedicationOfferingResource | JsonResponse
    {
        $offering = $action->execute($request->validated(), $request->user());

        return MedicationOfferingResource::make($offering)
            ->response()
            ->setStatusCode(201);
    }
}
