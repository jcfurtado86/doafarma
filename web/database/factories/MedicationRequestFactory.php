<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\MedicationOffering;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicationRequest>
 */
class MedicationRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receptor_id'            => User::factory()->receptor(),
            'medication_offering_id' => MedicationOffering::factory(),
            'status'                 => 'pending',
        ];
    }

    /**
     * Indicate that the request is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the request is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the request is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Associate the request with a specific offering.
     */
    public function forOffering(MedicationOffering $offering): static
    {
        return $this->state(fn (array $attributes): array => [
            'medication_offering_id' => $offering->id,
        ]);
    }

    /**
     * Associate the request with a specific receptor.
     */
    public function forReceptor(User $receptor): static
    {
        return $this->state(fn (array $attributes): array => [
            'receptor_id' => $receptor->id,
        ]);
    }
}
