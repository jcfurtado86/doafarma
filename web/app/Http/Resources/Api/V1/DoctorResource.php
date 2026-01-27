<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

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
            'crm'    => $this->crm,
            'crm_uf' => $this->crm_uf,
        ];
    }
}
