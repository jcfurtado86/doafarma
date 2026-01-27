<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use App\Models\MedicationOffering;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin MedicationOffering
 * @property Carbon $expires_at
 */
class MedicationOfferingResource extends JsonResource
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
            'id'         => $this->id,
            'lot_number' => $this->lot_number,
            'expires_at' => $this->expires_at->toDateString(),
            'quantity'   => $this->quantity,
            'drug'       => DrugResource::make($this->whenLoaded('drug')),
            'user'       => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
