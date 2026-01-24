<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\Doctor;
use App\Models\MedicationAppointment;
use Illuminate\Database\Eloquent\Collection;

class ListDoctorDonationHistoryAction
{
    /**
     * Execute the action to list doctor's completed donations (history).
     *
     * @return Collection<int, MedicationAppointment>
     */
    public function execute(Doctor $doctor): Collection
    {
        return MedicationAppointment::query()
            ->whereHas('medicationRequest.medicationOffering', fn ($q) => $q->where('doctor_id', $doctor->id))
            ->completed()
            ->with([
                'medicationRequest.medicationOffering.drug',
                'medicationRequest.receptor',
                'address',
            ])
            ->orderByDesc('updated_at')
            ->get();
    }
}
