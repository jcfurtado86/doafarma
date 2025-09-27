<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationOffering;

use App\Actions\MedicationOffering\ShowMedicationOfferingAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MedicationOfferingResource;
use App\Models\MedicationOffering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShowController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        MedicationOffering $medicationOffering,
        ShowMedicationOfferingAction $action
    ): MedicationOfferingResource {
        Gate::authorize('view', $medicationOffering);

        $medicationOffering = $action->execute($medicationOffering);

        return MedicationOfferingResource::make($medicationOffering);
    }
}
