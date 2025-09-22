<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Drug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicationOffering>
 */
class MedicationOfferingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id'  => Doctor::factory(),
            'drug_id'    => Drug::factory(),
            'lot_number' => $this->faker->bothify('LOT-#####'),
            'expires_at' => now()->addMonths($this->faker->numberBetween(1, 24))->toDateString(),
            'quantity'   => $this->faker->numberBetween(1, 500),
        ];
    }
}
