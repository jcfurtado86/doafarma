<?php

declare(strict_types = 1);

namespace App\Actions\Drug;

use App\Models\Drug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListDrugAction
{
    /**
     * Execute the action.
     *
     * @return LengthAwarePaginator<int, Drug>
     */
    public function execute(int $perPage = 15, string $sort = 'product_name', string $order = 'asc'): LengthAwarePaginator
    {
        return Drug::query()
            ->select(['id', 'product_name', 'substance', 'laboratory'])
            ->orderByRaw('LOWER(' . $sort . ') ' . $order)
            ->paginate($perPage);
    }
}
