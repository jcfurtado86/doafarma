<?php

declare(strict_types = 1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Drug>
 */
class DrugFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'substance'           => $this->faker->word(),
            'laboratory'          => $this->faker->word(),
            'registration_number' => $this->faker->unique()->word(),
            'product_name'        => $this->faker->word(),
            'presentation'        => $this->faker->text(),
            'stripe_color'        => $this->faker->word(),
        ];
    }
}
