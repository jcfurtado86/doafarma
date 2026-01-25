<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\DoctorRating;
use App\Models\MedicationAppointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorRating>
 */
class DoctorRatingFactory extends Factory
{
    protected $model = DoctorRating::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medication_appointment_id' => MedicationAppointment::factory()->completed(),
            'doctor_id'                 => Doctor::factory(),
            'receptor_id'               => User::factory()->receptor(),
            'rating'                    => $this->faker->numberBetween(1, 5),
            'comment'                   => $this->faker->optional(0.7)->sentence(),
        ];
    }

    /**
     * Associate the rating with a specific appointment.
     */
    public function forAppointment(MedicationAppointment $appointment): static
    {
        $request = $appointment->medicationRequest;

        return $this->state(fn (): array => [
            'medication_appointment_id' => $appointment->id,
            'doctor_id'                 => $request->medicationOffering->doctor_id,
            'receptor_id'               => $request->receptor_id,
        ]);
    }

    /**
     * Set a specific rating value.
     */
    public function withRating(int $rating): static
    {
        return $this->state(fn (): array => [
            'rating' => $rating,
        ]);
    }

    /**
     * Set a specific comment.
     */
    public function withComment(string $comment): static
    {
        return $this->state(fn (): array => [
            'comment' => $comment,
        ]);
    }

    /**
     * Set no comment.
     */
    public function withoutComment(): static
    {
        return $this->state(fn (): array => [
            'comment' => null,
        ]);
    }
}
