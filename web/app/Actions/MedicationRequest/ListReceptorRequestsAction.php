<?php

declare(strict_types = 1);

namespace App\Actions\MedicationRequest;

use App\Models\MedicationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListReceptorRequestsAction
{
    /**
     * Execute the action to list all requests made by a receptor.
     *
     * @param User $receptor The receptor user
     *
     * @return Collection<int, MedicationRequest>
     */
    public function execute(User $receptor): Collection
    {
        return MedicationRequest::where('receptor_id', $receptor->id)
            ->with(['medicationOffering.drug', 'medicationOffering.doctor.user'])
            ->orderByDesc('created_at')
            ->get();
    }
}
