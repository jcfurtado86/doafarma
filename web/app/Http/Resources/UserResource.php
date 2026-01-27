<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'name'              => $this->name,
            'email'             => $this->email,
            'cpf'               => $this->when($this->cpf !== null, $this->cpf),
            'phone_number'      => $this->phone_number,
            'role'              => $this->role,
            'status'            => $this->status,
            'status_changed_at' => $this->status_changed_at,
            'doctor'            => new DoctorResource($this->whenLoaded('doctor')),
            'addresses'         => AddressResource::collection($this->whenLoaded('addresses')),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
