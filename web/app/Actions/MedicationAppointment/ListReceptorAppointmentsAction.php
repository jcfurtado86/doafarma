<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\MedicationAppointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListReceptorAppointmentsAction
{
    /**
     * Execute the action to list receptor's appointments.
     *
     * @return Collection<int, MedicationAppointment>
     */
    public function execute(User $receptor, ?string $status = null): Collection
    {
        $query = MedicationAppointment::query()
            ->whereHas('medicationRequest', function ($q) use ($receptor): void {
                $q->where('receptor_id', $receptor->id);
            })
            ->with(MedicationAppointment::RELATIONS_FOR_RECEPTOR)
            ->upcoming();

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get();
    }
}
