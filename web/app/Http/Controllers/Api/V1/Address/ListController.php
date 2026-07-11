<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\ListAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\ListAddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ListAddressRequest $request, ListAddressAction $action): AnonymousResourceCollection
    {
        $addresses = $action->execute($request->user());

        return AddressResource::collection($addresses);
    }
}
