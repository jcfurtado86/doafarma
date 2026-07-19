<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Http\Resources\Api\V1\AddressResource as V1AddressResource;

/**
 * Kept only because App\Http\Resources\UserResource (used by the doctor-registration
 * response) still references this namespace. Delegates to the canonical Api\V1 resource
 * instead of duplicating its field list.
 */
class AddressResource extends V1AddressResource
{
}
