<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Drug
 */
class DrugResource extends JsonResource
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
            'id'           => $this->id,
            'product_name' => $this->product_name,
            'substance'    => $this->substance,
            'laboratory'   => $this->laboratory,
        ];
    }
}
