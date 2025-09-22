<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationOffering;

use App\Actions\MedicationOffering\UpdateMedicationOfferingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicationOffering\UpdateMedicationOfferingRequest;
use App\Http\Resources\Api\V1\MedicationOfferingResource;
use App\Models\MedicationOffering;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        UpdateMedicationOfferingRequest $request,
        MedicationOffering $medicationOffering,
        UpdateMedicationOfferingAction $action
    ): MedicationOfferingResource {
        $updatedOffering = $action->execute(
            $medicationOffering,
            $request->validated()
        );

        return MedicationOfferingResource::make($updatedOffering);
    }
}
