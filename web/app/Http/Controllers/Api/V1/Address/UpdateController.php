<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\UpdateAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\UpdateAddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use App\Models\Address;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        UpdateAddressRequest $request,
        Address $address,
        UpdateAddressAction $action
    ): AddressResource {
        $updatedAddress = $action->execute($address, $request->validated());

        return AddressResource::make($updatedAddress);
    }
}
