<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Drug;

use App\Actions\Drug\SearchDrugAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Drug\SearchRequest;
use App\Http\Resources\Api\V1\DrugResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SearchRequest $request, SearchDrugAction $action): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $paginator = $action->execute(
            query: (string) ($validated['q'] ?? ''),
            perPage: (int) ($validated['per_page'] ?? 15),
            sort: (string) ($validated['sort'] ?? 'id'),
            order: (string) ($validated['order'] ?? 'asc')
        );

        return DrugResource::collection($paginator);
    }
}
