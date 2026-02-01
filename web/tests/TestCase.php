<?php

declare(strict_types = 1);

namespace Tests;

use App\Enums\TokenAbility;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set the currently logged in user for the application.
     *
     * When guard is 'sanctum', uses Sanctum::actingAs with 'access' ability by default.
     * This ensures tests properly simulate real API authentication with token abilities.
     *
     * @param  array<string>  $abilities
     */
    public function actingAs(Authenticatable $user, mixed $guard = null, array $abilities = []): static
    {
        // Only use Sanctum for explicit 'sanctum' guard
        if ($guard === 'sanctum') {
            if ($abilities === []) {
                $abilities = [TokenAbility::Access->value];
            }

            Sanctum::actingAs($user, $abilities);

            return $this;
        }

        return parent::actingAs($user, $guard);
    }

    /**
     * Authenticate as user with access token ability (for API tests).
     */
    public function actingAsApiUser(Authenticatable $user): static
    {
        Sanctum::actingAs($user, [TokenAbility::Access->value]);

        return $this;
    }

    /**
     * Authenticate as user with refresh token ability.
     *
     * @param  array<string>  $additionalAbilities
     */
    public function actingAsWithRefreshToken(Authenticatable $user, array $additionalAbilities = []): static
    {
        $abilities = array_merge([TokenAbility::Refresh->value], $additionalAbilities);
        Sanctum::actingAs($user, $abilities);

        return $this;
    }
}
