<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\MedicationAppointment
 *
 * @property Carbon $scheduled_date
 */
class MedicationAppointmentResource extends JsonResource
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
            'id'                 => $this->id,
            'scheduled_date'     => $this->scheduled_date->toDateString(),
            'scheduled_time'     => $this->scheduled_time,
            'status'             => $this->status,
            'proposed_by'        => $this->proposed_by,
            'receptor_confirmed' => $this->receptor_confirmed,
            'doctor_confirmed'   => $this->doctor_confirmed,
            'created_at'         => $this->created_at->toIso8601String(),
            'updated_at'         => $this->updated_at->toIso8601String(),
            'medication_request' => MedicationRequestResource::make(
                $this->whenLoaded('medicationRequest')
            ),
            'address' => AddressResource::make($this->whenLoaded('address')),
        ];
    }
}
