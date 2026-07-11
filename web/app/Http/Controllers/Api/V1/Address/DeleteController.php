<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\DeleteAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\DeleteAddressRequest;
use App\Models\Address;
use Illuminate\Http\Response;

class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(DeleteAddressRequest $request, Address $address, DeleteAddressAction $action): Response
    {
        $action->execute($address);

        return response()->noContent();
    }
}
