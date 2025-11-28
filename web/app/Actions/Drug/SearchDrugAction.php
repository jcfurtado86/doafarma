<?php

declare(strict_types = 1);

namespace App\Actions\Drug;

use App\Models\Drug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchDrugAction
{
    /**
     * Execute the action.
     *
     * @return LengthAwarePaginator<int, Drug>
     */
    public function execute(string $query = '', int $perPage = 15, string $sort = 'id', string $order = 'asc'): LengthAwarePaginator
    {
        $drugQuery = Drug::query();

        if ($query !== '') {
            $this->applySearchFilter($drugQuery, $query);
        }

        $this->applySorting($drugQuery, $sort, $order);

        return $drugQuery
            ->select(['id', 'product_name', 'substance', 'laboratory'])
            ->paginate($perPage);
    }

    /**
     * Apply search filter to the query.
     *
     * @param Builder<Drug> $builder
     */
    private function applySearchFilter($builder, string $query): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $builder->whereRaw(
                "to_tsvector('portuguese', product_name || ' ' || substance || ' ' || laboratory) @@ plainto_tsquery('portuguese', ?)",
                [$query]
            );

            return;
        }

        $pattern = '%' . mb_strtolower($query) . '%';
        $builder->where(fn ($b) => $b->whereRaw('lower(product_name) LIKE ?', [$pattern])
            ->orWhereRaw('lower(substance) LIKE ?', [$pattern])
            ->orWhereRaw('lower(laboratory) LIKE ?', [$pattern]));
    }

    /**
     * Apply sorting to the query.
     *
     * @param Builder<Drug> $builder
     */
    private function applySorting($builder, string $sort, string $order): void
    {
        if (in_array($sort, ['product_name', 'substance', 'laboratory'], true)) {
            $builder->orderByRaw('LOWER(' . $sort . ') ' . $order);
        } else {
            $builder->orderBy($sort, $order);
        }
    }
}
