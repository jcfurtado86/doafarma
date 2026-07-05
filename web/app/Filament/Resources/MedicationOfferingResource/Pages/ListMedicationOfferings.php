<?php

declare(strict_types = 1);

namespace App\Filament\Resources\MedicationOfferingResource\Pages;

use App\Filament\Resources\MedicationOfferingResource;
use App\Models\MedicationOffering;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Override;

class ListMedicationOfferings extends ListRecords
{
    #[Override]
    protected static string $resource = MedicationOfferingResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return Builder<MedicationOffering>
     */
    #[Override]
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['drug', 'doctor.user']);
    }
}
