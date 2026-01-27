<?php

declare(strict_types = 1);

namespace App\Filament\Resources\MedicationAppointmentResource\Pages;

use App\Filament\Resources\MedicationAppointmentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicationAppointment extends ViewRecord
{
    protected static string $resource = MedicationAppointmentResource::class;
}
