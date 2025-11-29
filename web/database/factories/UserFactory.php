<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'cpf'               => fake()->unique()->numerify('###########'),
            'role'              => 'receptor',
            'email_verified_at' => now(),
            'phone_number'      => fake()->numerify('###########'),
            'terms_accepted'    => true,
            'terms_accepted_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function doctor(?string $crm = null, ?string $crm_uf = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => 'doctor',
        ])->afterCreating(function ($user) use ($crm, $crm_uf): void {
            $doctorAttributes = array_filter([
                'user_id' => $user->id,
                'crm'     => $crm,
                'crm_uf'  => $crm_uf,
            ]);

            Doctor::factory()->create($doctorAttributes);
        });
    }

    /**
     * Indicate that the user is a receptor (patient).
     */
    public function receptor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => 'receptor',
        ]);
    }
}
