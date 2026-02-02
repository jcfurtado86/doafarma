<?php

declare(strict_types = 1);

namespace App\Actions\MedicationOffering;

use App\Models\MedicationOffering;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchMedicationOfferingsAction
{
    private const PER_PAGE = 50;

    /**
     * Execute the search action with pagination.
     *
     * @return LengthAwarePaginator<int, MedicationOffering>
     */
    public function execute(?string $query = null, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $baseQuery = MedicationOffering::query()
            ->where('quantity', '>', 0)
            ->with(['drug', 'doctor.user']);

        // Se não há termo de busca, retorna todas as ofertas ativas paginadas
        if ($query === null || $query === '') {
            return $baseQuery->paginate($perPage);
        }

        $searchTerm = '%' . $query . '%';

        return $baseQuery
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
            ->paginate($perPage);
    }
}
