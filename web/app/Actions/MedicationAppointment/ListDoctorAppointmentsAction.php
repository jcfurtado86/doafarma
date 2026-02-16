<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\Doctor;
use App\Models\MedicationAppointment;
use Illuminate\Database\Eloquent\Collection;

class ListDoctorAppointmentsAction
{
    /**
     * Execute the action to list doctor's received appointments.
     *
     * @return Collection<int, MedicationAppointment>
     */
    public function execute(Doctor $doctor, ?string $status = null): Collection
    {
        $query = MedicationAppointment::query()
            ->whereHas('medicationRequest.medicationOffering', function ($q) use ($doctor): void {
                $q->where('doctor_id', $doctor->id);
            })
            ->with(MedicationAppointment::RELATIONS_FOR_DOCTOR)
            ->upcoming();

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get();
    }
}
