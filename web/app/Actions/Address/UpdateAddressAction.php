<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;

class UpdateAddressAction
{
    /**
     * Execute the action.
     *
     * @param array<string, mixed> $data validated payload
     */
    public function execute(Address $address, array $data): Address
    {
        $address->update($data);

        return $address->fresh();
    }
}
