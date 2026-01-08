<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\MedicationOffering
 * @property Carbon $expires_at
 */
class MedicationOfferingSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'quantity'   => $this->quantity,
            'lot_number' => $this->lot_number,
            'expires_at' => $this->expires_at->toDateString(),
            'drug'       => [
                'id'           => $this->drug->id,
                'product_name' => $this->drug->product_name,
                'substance'    => $this->drug->substance,
                'presentation' => $this->drug->presentation,
                'laboratory'   => $this->drug->laboratory,
            ],
            'doctor' => [
                'id'   => $this->doctor->id,
                'name' => $this->doctor->user->name,
            ],
        ];
    }
}
