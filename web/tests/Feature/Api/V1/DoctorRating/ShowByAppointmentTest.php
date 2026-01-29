<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\Doctor;
use App\Models\DoctorRating;
use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

// Helper function to create a completed appointment scenario
function createCompletedAppointmentForShow(User $receptor, ?Doctor $doctor = null): MedicationAppointment
{
    $doctor ??= Doctor::factory()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    return MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);
}

// Happy Path Tests

it('should return null when appointment has no rating', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $appointment = createCompletedAppointmentForShow($receptor);

    actingAs($receptor, 'sanctum');

    $response = getJson("/api/v1/doctor-ratings/appointment/{$appointment->id}");

    $response->assertOk()
        ->assertJsonPath('data', null);
});

it('should return existing rating when appointment has been rated', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $doctor      = Doctor::factory()->create();
    $appointment = createCompletedAppointmentForShow($receptor, $doctor);

    $rating = DoctorRating::factory()->create([
        'medication_appointment_id' => $appointment->id,
        'doctor_id'                 => $doctor->id,
        'receptor_id'               => $receptor->id,
        'rating'                    => 5,
        'comment'                   => 'Excelente!',
    ]);

    actingAs($receptor, 'sanctum');

    $response = getJson("/api/v1/doctor-ratings/appointment/{$appointment->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $rating->id)
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.comment', 'Excelente!');
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $appointment = createCompletedAppointmentForShow($receptor);

    getJson("/api/v1/doctor-ratings/appointment/{$appointment->id}")
        ->assertUnauthorized();
});

it('should return 403 when receptor checks another receptors appointment', function (): void {
    $receptor1   = User::factory()->receptor()->create();
    $receptor2   = User::factory()->receptor()->create();
    $appointment = createCompletedAppointmentForShow($receptor1);

    actingAs($receptor2, 'sanctum');

    getJson("/api/v1/doctor-ratings/appointment/{$appointment->id}")
        ->assertForbidden();
});

// Edge Cases

it('should return 404 for non-existent appointment', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor, 'sanctum');

    getJson('/api/v1/doctor-ratings/appointment/99999')
        ->assertNotFound();
});
