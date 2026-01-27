<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use App\Models\DoctorRating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin DoctorRating
 */
class DoctorRatingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'rating'                 => $this->rating,
            'comment'                => $this->comment,
            'created_at'             => $this->created_at->toIso8601String(),
            'updated_at'             => $this->updated_at->toIso8601String(),
            'medication_appointment' => MedicationAppointmentResource::make(
                $this->whenLoaded('medicationAppointment')
            ),
            'doctor' => $this->whenLoaded('doctor', fn (): array => [
                'id'   => $this->doctor->id,
                'name' => $this->doctor->user->name,
            ]),
            'receptor' => $this->whenLoaded('receptor', fn (): array => [
                'id'   => $this->receptor->id,
                'name' => $this->receptor->name,
            ]),
        ];
    }
}
