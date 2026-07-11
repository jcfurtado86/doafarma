<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;
use App\Models\User;

class StoreAddressAction
{
    /**
     * Execute the action.
     *
     * @param array<string, mixed> $data validated payload
     */
    public function execute(User $user, array $data): Address
    {
        $address = $user->addresses()->create($data);

        if ($user->default_address_id === null) {
            $user->update(['default_address_id' => $address->id]);
        }

        return $address->fresh();
    }
}
