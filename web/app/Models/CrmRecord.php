<?php

declare(strict_types = 1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Carbon $expires_at
 * @property Carbon $verified_at
 * @property array<int, string>|null $specialties
 * @property array<string, mixed>|null $raw_response
 */
class CrmRecord extends Model
{
    protected $fillable = [
        'crm',
        'uf',
        'doctor_name',
        'status',
        'specialties',
        'source',
        'verified_at',
        'expires_at',
        'raw_response',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specialties'  => 'array',
            'raw_response' => 'array',
            'verified_at'  => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'ativo';
    }
}
