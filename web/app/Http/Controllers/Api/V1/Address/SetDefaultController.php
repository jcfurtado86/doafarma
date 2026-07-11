<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\SetDefaultAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;

class SetDefaultController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Address $address, SetDefaultAddressAction $action): AddressResource
    {
        $this->authorize('update', $address);

        $action->execute($request->user(), $address);

        return AddressResource::make($address);
    }
}
