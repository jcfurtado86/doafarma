<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\MedicationRequest
 */
class MedicationRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        // Only expose full contact details when the request is confirmed
        // This protects receptor privacy while allowing necessary communication
        $isConfirmed      = $this->status === 'confirmed';
        $receptorData     = $this->whenLoaded('receptor');
        $receptorResource = $receptorData
            ? ($isConfirmed
                ? ReceptorResource::make($receptorData)->withContactDetails()
                : ReceptorResource::make($receptorData))
            : null;

        return [
            'id'                  => $this->id,
            'status'              => $this->status,
            'created_at'          => $this->created_at->toIso8601String(),
            'updated_at'          => $this->updated_at->toIso8601String(),
            'medication_offering' => MedicationOfferingSearchResource::make(
                $this->whenLoaded('medicationOffering')
            ),
            'receptor' => $receptorResource,
        ];
    }
}
