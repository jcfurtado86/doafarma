<?php

declare(strict_types = 1);

namespace App\Filament\Resources\MedicationRequestResource\Pages;

use App\Filament\Resources\MedicationRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicationRequest extends ViewRecord
{
    #[\Override]
    protected static string $resource = MedicationRequestResource::class;
}
