<?php

declare(strict_types = 1);

namespace App\Enums;

enum UserRole: string
{
    case Doctor   = 'doctor';
    case Receptor = 'receptor';
    case Admin    = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Doctor   => 'Médico',
            self::Receptor => 'Receptor',
            self::Admin    => 'Administrador',
        };
    }
}
