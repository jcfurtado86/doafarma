<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\SetDefaultAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\SetDefaultAddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use App\Models\Address;

class SetDefaultController extends Controller
{
    /**
     * Set the address as the authenticated user's default.
     */
    public function __invoke(SetDefaultAddressRequest $request, Address $address, SetDefaultAddressAction $action): AddressResource
    {
        $action->execute($request->user(), $address);

        return AddressResource::make($address);
    }
}
