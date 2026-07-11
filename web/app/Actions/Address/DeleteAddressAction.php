<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;

class DeleteAddressAction
{
    /**
     * Execute the action.
     */
    public function execute(Address $address): void
    {
        $address->delete();
    }
}
