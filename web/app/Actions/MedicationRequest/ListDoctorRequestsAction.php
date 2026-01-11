<?php

declare(strict_types = 1);

namespace App\Actions\MedicationRequest;

use App\Models\MedicationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListDoctorRequestsAction
{
    /**
     * Execute the action to list all requests received by a doctor.
     *
     * @param User        $doctor The doctor user
     * @param string|null $status Optional status filter (pending, confirmed, rejected)
     *
     * @return Collection<int, MedicationRequest>
     */
    public function execute(User $doctor, ?string $status = null): Collection
    {
        $doctorModel = $doctor->doctor;

        if ($doctorModel === null) {
            return new Collection();
        }

        $query = MedicationRequest::whereHas('medicationOffering', function ($q) use ($doctorModel): void {
            $q->where('doctor_id', $doctorModel->id);
        })
            ->with(['receptor', 'medicationOffering.drug']);

        if ($status !== null && in_array($status, ['pending', 'confirmed', 'rejected'], true)) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->get();
    }
}
