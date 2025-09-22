<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 * @property Carbon|null $terms_accepted_at
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $data = [
            'id'   => $this->id,
            'name' => $this->name,

            'doctor_profile' => DoctorResource::make($this->whenLoaded('doctor')),
            'addresses'      => AddressResource::collection($this->whenLoaded('addresses')),
        ];

        if ($request->user()?->id === $this->id) {
            $data['email']             = $this->email;
            $data['phone_number']      = $this->phone_number;
            $data['email_verified']    = $this->hasVerifiedEmail();
            $data['terms_accepted']    = (bool) $this->terms_accepted;
            $data['terms_accepted_at'] = $this->when(
                $this->terms_accepted,
                $this->terms_accepted_at?->toIso8601String()
            );
        }

        return $data;
    }
}
