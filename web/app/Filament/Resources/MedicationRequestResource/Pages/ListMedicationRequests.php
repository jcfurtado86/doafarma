<?php

declare(strict_types = 1);

namespace App\Filament\Resources\MedicationRequestResource\Pages;

use App\Filament\Resources\MedicationRequestResource;
use App\Models\MedicationRequest;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Override;

class ListMedicationRequests extends ListRecords
{
    #[Override]
    protected static string $resource = MedicationRequestResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return Builder<MedicationRequest>
     */
    #[Override]
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with([
                'receptor',
                'medicationOffering.drug',
                'medicationOffering.doctor.user',
            ]);
    }
}
