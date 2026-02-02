<?php

declare(strict_types = 1);

namespace App\Actions\MedicationAppointment;

use App\Models\MedicationAppointment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListReceptorHistoryAction
{
    private const PER_PAGE = 15;

    /**
     * Execute the action to list receptor's completed appointments (medication history) with pagination.
     *
     * @return LengthAwarePaginator<int, MedicationAppointment>
     */
    public function execute(User $receptor, int $perPage = self::PER_PAGE): LengthAwarePaginator
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
            ->paginate($perPage);
    }
}
