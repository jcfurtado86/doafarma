<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

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
            'id'                => $this->id,
            'label'             => $this->label,
            'cep'               => $this->cep,
            'uf'                => $this->uf,
            'city'              => $this->city,
            'neighborhood'      => $this->neighborhood,
            'street'            => $this->street,
            'number'            => $this->number,
            'complement'        => $this->complement,
            'formatted_address' => $this->formatted_address,
            'is_default'        => $request->user() !== null
                && $this->user_id === $request->user()->id
                && $this->id === $request->user()->default_address_id,
        ];
    }
}
