<?php

declare(strict_types = 1);

namespace App\Filament\Resources\MedicationAppointmentResource\Pages;

use App\Filament\Resources\MedicationAppointmentResource;
use Filament\Resources\Pages\ListRecords;

class ListMedicationAppointments extends ListRecords
{
    protected static string $resource = MedicationAppointmentResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
