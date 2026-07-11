<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;
use App\Models\User;

class SetDefaultAddressAction
{
    /**
     * Execute the action.
     */
    public function execute(User $user, Address $address): void
    {
        $user->update(['default_address_id' => $address->id]);
    }
}
