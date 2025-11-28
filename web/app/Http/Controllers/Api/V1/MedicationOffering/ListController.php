<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationOffering;

use App\Actions\MedicationOffering\ListMedicationOfferingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicationOffering\ListMedicationOfferingRequest;
use App\Http\Resources\Api\V1\MedicationOfferingResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ListMedicationOfferingRequest $request, ListMedicationOfferingAction $action): AnonymousResourceCollection
    {
        $perPage  = (int) $request->input('per_page', 15);
        $includes = [];

        if ($request->query('include') === 'drug') {
            $includes[] = 'drug';
        }

        $offerings = $action->execute($request->user(), $includes, $perPage);

        return MedicationOfferingResource::collection($offerings);
    }
}
