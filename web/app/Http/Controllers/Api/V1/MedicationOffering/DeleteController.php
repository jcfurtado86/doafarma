<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationOffering;

use App\Actions\MedicationOffering\DeleteMedicationOfferingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicationOffering\DeleteMedicationOfferingRequest;
use App\Models\MedicationOffering;
use Illuminate\Http\Response;

class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(DeleteMedicationOfferingRequest $request, MedicationOffering $medicationOffering, DeleteMedicationOfferingAction $action): Response
    {
        $action->execute($medicationOffering);

        return response()->noContent();
    }
}
