<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationOffering;

use App\Actions\MedicationOffering\DeleteMedicationOfferingAction;
use App\Http\Controllers\Controller;
use App\Models\MedicationOffering;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(MedicationOffering $medicationOffering, DeleteMedicationOfferingAction $action): Response
    {
        Gate::authorize('delete', $medicationOffering);

        $action->execute($medicationOffering);

        return response()->noContent();
    }
}
