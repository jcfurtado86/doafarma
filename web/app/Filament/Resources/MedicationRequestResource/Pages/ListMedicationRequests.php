<?php

declare(strict_types = 1);

namespace App\Filament\Resources\MedicationRequestResource\Pages;

use App\Filament\Resources\MedicationRequestResource;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListMedicationRequests extends ListRecords
{
    protected static string $resource = MedicationRequestResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
