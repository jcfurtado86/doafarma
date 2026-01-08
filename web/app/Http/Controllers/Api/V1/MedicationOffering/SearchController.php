<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationOffering;

use App\Actions\MedicationOffering\SearchMedicationOfferingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicationOffering\SearchMedicationOfferingsRequest;
use App\Http\Resources\Api\V1\MedicationOfferingSearchResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        SearchMedicationOfferingsRequest $request,
        SearchMedicationOfferingsAction $action
    ): AnonymousResourceCollection {
        $query = $request->validated('q');

        $offerings = $action->execute($query);

        return MedicationOfferingSearchResource::collection($offerings);
    }
}
