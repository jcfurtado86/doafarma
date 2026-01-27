<?php

declare(strict_types = 1);

namespace App\Actions\MedicationOffering;

use App\Models\MedicationOffering;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class ListMedicationOfferingAction
{
    /**
     * Execute the action.
     *
     * @param array<string> $includes
     * @return LengthAwarePaginator<int, MedicationOffering>
     */
    public function execute(User $user, array $includes = [], int $perPage = 15): LengthAwarePaginator
    {
        $doctor = $user->doctor;

        if ($doctor === null) {
            throw new RuntimeException('User is not a doctor');
        }

        return $doctor
            ->medicationOfferings()
            ->with($includes)
            ->latest()
            ->paginate($perPage);
    }
}
