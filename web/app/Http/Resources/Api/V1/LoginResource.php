<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
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
            'data' => [
                'user'  => UserResource::make($this->resource['user']),
                'token' => $this->resource['token'],
            ],
            'message' => 'Login realizado com sucesso',
        ];
    }
}
