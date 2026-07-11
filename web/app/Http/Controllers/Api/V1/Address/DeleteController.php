<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\DeleteAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\DeleteAddressRequest;
use App\Models\Address;
use App\Models\MedicationAppointment;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(DeleteAddressRequest $request, Address $address, DeleteAddressAction $action): Response
    {
        if (MedicationAppointment::where('address_id', $address->id)->exists()) {
            throw new UnprocessableEntityHttpException(
                'Este endereço está vinculado a um ou mais agendamentos e não pode ser removido.'
            );
        }

        $action->execute($address);

        return response()->noContent();
    }
}
