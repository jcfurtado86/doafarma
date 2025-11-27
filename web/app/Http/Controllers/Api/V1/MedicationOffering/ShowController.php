<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationOffering;

use App\Actions\MedicationOffering\ShowMedicationOfferingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicationOffering\ShowMedicationOfferingRequest;
use App\Http\Resources\Api\V1\MedicationOfferingResource;
use App\Models\MedicationOffering;

class ShowController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        ShowMedicationOfferingRequest $request,
        MedicationOffering $medicationOffering,
        ShowMedicationOfferingAction $action
    ): MedicationOfferingResource {
        $medicationOffering = $action->execute($medicationOffering);

        return MedicationOfferingResource::make($medicationOffering);
    }
}
