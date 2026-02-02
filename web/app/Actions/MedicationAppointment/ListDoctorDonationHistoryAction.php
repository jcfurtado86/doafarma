<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\Doctor;
use App\Models\MedicationAppointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListDoctorDonationHistoryAction
{
    private const PER_PAGE = 15;

    /**
     * Execute the action to list doctor's completed donations (history) with pagination.
     *
     * @return LengthAwarePaginator<int, MedicationAppointment>
     */
    public function execute(Doctor $doctor, int $perPage = self::PER_PAGE): LengthAwarePaginator
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
            ->paginate($perPage);
    }
}
