<?php

declare(strict_types = 1);

namespace App\Filament\Resources\MedicationAppointmentResource\Pages;

use App\Filament\Resources\MedicationAppointmentResource;
use App\Models\MedicationAppointment;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Override;

class ListMedicationAppointments extends ListRecords
{
    #[Override]
    protected static string $resource = MedicationAppointmentResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return Builder<MedicationAppointment>
     */
    #[Override]
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with([
                'medicationRequest.receptor',
                'medicationRequest.medicationOffering.drug',
                'medicationRequest.medicationOffering.doctor.user',
            ]);
    }
}
