<?php

declare(strict_types = 1);

namespace App\Enums;

enum CrmStatus: string
{
    case Active    = 'ativo';
    case Inactive  = 'inativo';
    case Canceled  = 'cancelado';
    case Suspended = 'suspenso';
    case NotFound  = 'not_found';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isRejectable(): bool
    {
        return in_array($this, [self::Inactive, self::Canceled, self::Suspended], true);
    }
}
