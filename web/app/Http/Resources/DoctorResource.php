<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Doctor
 */
class DoctorResource extends JsonResource
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
            'id'              => $this->id,
            'crm'             => $this->crm,
            'crm_uf'          => $this->crm_uf,
            'crm_verified_at' => $this->crm_verified_at,
            'crm_source'      => $this->crm_source,
            'specialty'       => $this->specialty,
        ];
    }
}
