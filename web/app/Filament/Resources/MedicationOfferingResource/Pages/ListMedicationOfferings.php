<?php

declare(strict_types = 1);

namespace App\Filament\Resources\MedicationOfferingResource\Pages;

use App\Filament\Resources\MedicationOfferingResource;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListMedicationOfferings extends ListRecords
{
    protected static string $resource = MedicationOfferingResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
