<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListAddressAction
{
    /**
     * Execute the action.
     *
     * @return Collection<int, Address>
     */
    public function execute(User $user): Collection
    {
        return $user->addresses()->orderBy('id')->get();
    }
}
