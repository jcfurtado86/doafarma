<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\MedicationAppointment;
use App\Models\MedicationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicationAppointment>
 */
class MedicationAppointmentFactory extends Factory
{
    protected $model = MedicationAppointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medication_request_id' => MedicationRequest::factory()->confirmed(),
            'address_id'            => $this->getAddressForRequest(...),
            'scheduled_date'        => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'scheduled_time'        => $this->faker->time('H:i'),
            'status'                => 'proposed',
            'proposed_by'           => 'receptor',
            'receptor_confirmed'    => false,
            'doctor_confirmed'      => false,
        ];
    }

    /**
     * Get or create an address for the doctor of the medication request.
     *
     * @param array<string, mixed> $attributes
     */
    private function getAddressForRequest(array $attributes): int
    {
        $requestId = $attributes['medication_request_id'];
        $request   = MedicationRequest::find($requestId);

        if ($request === null) {
            // Fallback: create a medication request and get address
            $request = MedicationRequest::factory()->confirmed()->create();
        }

        $doctorUserId = $request->medicationOffering->doctor->user_id;
        $address      = Address::where('user_id', $doctorUserId)->first()
            ?? Address::factory()->create(['user_id' => $doctorUserId]);

        return $address->id;
    }

    /**
     * Indicate that the appointment is proposed (awaiting acceptance).
     */
    public function proposed(): static
    {
        return $this->state(fn (): array => [
            'status'             => 'proposed',
            'receptor_confirmed' => false,
            'doctor_confirmed'   => false,
        ]);
    }

    /**
     * Indicate that the appointment was proposed by the receptor.
     */
    public function proposedByReceptor(): static
    {
        return $this->state(fn (): array => [
            'proposed_by' => 'receptor',
        ]);
    }

    /**
     * Indicate that the appointment was proposed by the doctor.
     */
    public function proposedByDoctor(): static
    {
        return $this->state(fn (): array => [
            'proposed_by' => 'doctor',
        ]);
    }

    /**
     * Indicate that the appointment is confirmed (both parties agreed).
     */
    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status'             => 'confirmed',
            'receptor_confirmed' => false,
            'doctor_confirmed'   => false,
        ]);
    }

    /**
     * Indicate that the appointment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status'             => 'completed',
            'receptor_confirmed' => true,
            'doctor_confirmed'   => true,
        ]);
    }

    /**
     * Indicate that the receptor has confirmed delivery.
     */
    public function receptorConfirmed(): static
    {
        return $this->state(fn (): array => [
            'receptor_confirmed' => true,
        ]);
    }

    /**
     * Indicate that the doctor has confirmed delivery.
     */
    public function doctorConfirmed(): static
    {
        return $this->state(fn (): array => [
            'doctor_confirmed' => true,
        ]);
    }

    /**
     * Associate the appointment with a specific medication request.
     */
    public function forRequest(MedicationRequest $request): static
    {
        $doctorUserId = $request->medicationOffering->doctor->user_id;
        $address      = Address::where('user_id', $doctorUserId)->first()
            ?? Address::factory()->create(['user_id' => $doctorUserId]);

        return $this->state(fn (): array => [
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);
    }
}
