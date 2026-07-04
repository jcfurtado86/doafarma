<?php

declare(strict_types = 1);

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    #[\Override]
    protected static string $resource = ActivityLogResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
