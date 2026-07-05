<?php

declare(strict_types = 1);

namespace App\Filament\Resources\MedicationOfferingResource\Pages;

use App\Filament\Resources\MedicationOfferingResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicationOffering extends ViewRecord
{
    #[\Override]
    protected static string $resource = MedicationOfferingResource::class;
}
