<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Address
 */
class AddressResource extends JsonResource
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
            'id'            => $this->id,
            'location_name' => $this->location_name,
            'full_address'  => $this->full_address,
            'complement'    => $this->complement,
            'cep'           => $this->cep,
        ];
    }
}
