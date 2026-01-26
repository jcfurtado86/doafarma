<?php

declare(strict_types = 1);

namespace App\Enums;

enum UserStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Pendente',
            self::Approved => 'Aprovado',
            self::Rejected => 'Rejeitado',
        };
    }

    public function canAccess(): bool
    {
        return $this === self::Approved;
    }
}
