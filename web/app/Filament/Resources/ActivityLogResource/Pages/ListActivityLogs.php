<?php

declare(strict_types = 1);

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Override;
use Spatie\Activitylog\Models\Activity;

class ListActivityLogs extends ListRecords
{
    #[Override]
    protected static string $resource = ActivityLogResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return Builder<Activity>
     */
    #[Override]
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['causer']);
    }
}
