<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Drug;

use App\Actions\Drug\ListDrugAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DrugResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, ListDrugAction $action): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);
        $sort    = (string) $request->input('sort', 'product_name');
        $order   = (string) $request->input('order', 'asc');

        $drugs = $action->execute($perPage, $sort, $order);

        return DrugResource::collection($drugs);
    }
}
