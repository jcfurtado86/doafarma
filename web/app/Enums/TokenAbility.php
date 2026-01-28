<?php

declare(strict_types = 1);

namespace App\Enums;

enum TokenAbility: string
{
    case Access  = 'access';
    case Refresh = 'refresh';
}
