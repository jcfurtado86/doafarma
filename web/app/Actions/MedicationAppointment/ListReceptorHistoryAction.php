<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\MedicationAppointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListReceptorHistoryAction
{
    /**
     * Execute the action to list receptor's completed appointments (medication history).
     *
     * @return Collection<int, MedicationAppointment>
     */
    public function execute(User $receptor): Collection
    {
        return MedicationAppointment::query()
            ->whereHas('medicationRequest', fn ($q) => $q->where('receptor_id', $receptor->id))
            ->completed()
            ->with([
                'medicationRequest.medicationOffering.drug',
                'medicationRequest.medicationOffering.doctor.user',
                'address',
            ])
            ->orderByDesc('updated_at')
            ->get();
    }
}
