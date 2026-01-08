<?php

declare(strict_types = 1);

namespace App\Actions\MedicationOffering;

use App\Models\MedicationOffering;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SearchMedicationOfferingsAction
{
    /**
     * Execute the search action.
     *
     * @return Collection<int, MedicationOffering>
     */
    public function execute(string $query): Collection
    {
        $searchTerm = '%' . $query . '%';

        return MedicationOffering::query()
            ->where('quantity', '>', 0)
            ->whereHas('drug', function ($q) use ($searchTerm): void {
                if (DB::getDriverName() === 'pgsql') {
                    $q->where('product_name', 'ILIKE', $searchTerm)
                        ->orWhere('substance', 'ILIKE', $searchTerm);
                } else {
                    // SQLite fallback - use LIKE with COLLATE NOCASE
                    $q->where('product_name', 'LIKE', $searchTerm)
                        ->orWhere('substance', 'LIKE', $searchTerm);
                }
            })
            ->with(['drug', 'doctor.user'])
            ->get();
    }
}
