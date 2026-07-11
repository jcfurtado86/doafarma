<?php

declare(strict_types = 1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label'        => fake()->company(),
            'cep'          => fake()->numerify('########'),
            'uf'           => fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR']),
            'city'         => fake()->city(),
            'neighborhood' => fake()->word(),
            'street'       => fake()->streetName(),
            'number'       => (string) fake()->buildingNumber(),
            'complement'   => fake()->optional()->secondaryAddress(),
        ];
    }
}
