<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationRequest;

use App\Actions\MedicationRequest\ListReceptorRequestsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MedicationRequestResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        ListReceptorRequestsAction $action
    ): AnonymousResourceCollection {
        $requests = $action->execute($request->user());

        return MedicationRequestResource::collection($requests);
    }
}
