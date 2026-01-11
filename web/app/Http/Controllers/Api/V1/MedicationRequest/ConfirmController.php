<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\MedicationRequest;

use App\Actions\MedicationRequest\ConfirmMedicationRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MedicationRequestResource;
use App\Models\MedicationRequest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ConfirmController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        MedicationRequest $medicationRequest,
        ConfirmMedicationRequestAction $action
    ): MedicationRequestResource {
        $this->authorize('confirm', $medicationRequest);

        if ($medicationRequest->status !== 'pending') {
            throw new UnprocessableEntityHttpException('Esta solicitação já foi processada.');
        }

        $updatedRequest = $action->execute($medicationRequest);

        return MedicationRequestResource::make($updatedRequest);
    }
}
