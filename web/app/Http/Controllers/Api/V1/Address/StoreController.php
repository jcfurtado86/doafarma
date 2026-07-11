<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\StoreAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\StoreAddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreAddressRequest $request, StoreAddressAction $action): AddressResource | JsonResponse
    {
        $address = $action->execute($request->user(), $request->validated());

        return AddressResource::make($address)
            ->response()
            ->setStatusCode(201);
    }
}
