<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class RegistrationResource extends JsonResource
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
            'data' => [
                'user'               => new UserResource($this->resource['user']),
                'access_token'       => $this->resource['access_token'],
                'refresh_token'      => $this->resource['refresh_token'],
                'expires_in'         => $this->resource['expires_in'],
                'refresh_expires_in' => $this->resource['refresh_expires_in'],
            ],
            'message' => 'Cadastro realizado com sucesso',
        ];
    }
}
