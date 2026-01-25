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
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

// Helper function to create a completed appointment scenario
function createCompletedAppointment(User $receptor, ?Doctor $doctor = null): MedicationAppointment
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

it('should allow receptor to rate a doctor after completed appointment', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $appointment = createCompletedAppointment($receptor);

    actingAs($receptor);

    $response = postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating'  => 5,
        'comment' => 'Excelente médico, muito atencioso!',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.comment', 'Excelente médico, muito atencioso!');

    assertDatabaseHas('doctor_ratings', [
        'medication_appointment_id' => $appointment->id,
        'rating'                    => 5,
        'comment'                   => 'Excelente médico, muito atencioso!',
    ]);
});

it('should allow rating without comment', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $appointment = createCompletedAppointment($receptor);

    actingAs($receptor);

    $response = postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating' => 4,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.rating', 4)
        ->assertJsonPath('data.comment', null);
});

it('should return correct doctor and receptor info in response', function (): void {
    $receptor    = User::factory()->receptor()->create(['name' => 'João Receptor']);
    $doctor      = Doctor::factory()->create();
    $appointment = createCompletedAppointment($receptor, $doctor);

    actingAs($receptor);

    $response = postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating' => 5,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.doctor.id', $doctor->id)
        ->assertJsonPath('data.receptor.name', 'João Receptor');
});

// Validation Error Tests

it('should return 422 when rating is missing', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $appointment = createCompletedAppointment($receptor);

    actingAs($receptor);

    postJson("/api/v1/doctor-ratings/{$appointment->id}", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['rating']);
});

it('should return 422 when rating is below 1', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $appointment = createCompletedAppointment($receptor);

    actingAs($receptor);

    postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating' => 0,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['rating']);
});

it('should return 422 when rating is above 5', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $appointment = createCompletedAppointment($receptor);

    actingAs($receptor);

    postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating' => 6,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['rating']);
});

it('should return 422 when comment exceeds max length', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $appointment = createCompletedAppointment($receptor);

    actingAs($receptor);

    postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating'  => 5,
        'comment' => str_repeat('a', 1001),
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['comment']);
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $appointment = createCompletedAppointment($receptor);

    postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating' => 5,
    ])
        ->assertUnauthorized();
});

it('should return 403 when doctor tries to rate themselves', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $doctor      = Doctor::factory()->create();
    $appointment = createCompletedAppointment($receptor, $doctor);

    actingAs($doctor->user);

    postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating' => 5,
    ])
        ->assertForbidden();
});

it('should return 403 when receptor tries to rate another receptors appointment', function (): void {
    $receptor1   = User::factory()->receptor()->create();
    $receptor2   = User::factory()->receptor()->create();
    $appointment = createCompletedAppointment($receptor1);

    actingAs($receptor2);

    postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating' => 5,
    ])
        ->assertForbidden();
});

// Business Rule Error Tests

it('should return 422 when appointment is not completed', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()->confirmed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);

    actingAs($receptor);

    postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating' => 5,
    ])
        ->assertStatus(422)
        ->assertJson(['message' => 'Apenas entregas concluídas podem ser avaliadas.']);
});

it('should return 409 when rating already exists for appointment', function (): void {
    $receptor    = User::factory()->receptor()->create();
    $doctor      = Doctor::factory()->create();
    $appointment = createCompletedAppointment($receptor, $doctor);

    // Create existing rating
    DoctorRating::factory()->create([
        'medication_appointment_id' => $appointment->id,
        'doctor_id'                 => $doctor->id,
        'receptor_id'               => $receptor->id,
        'rating'                    => 4,
    ]);

    actingAs($receptor);

    postJson("/api/v1/doctor-ratings/{$appointment->id}", [
        'rating' => 5,
    ])
        ->assertStatus(409)
        ->assertJson(['message' => 'Você já avaliou esta entrega.']);
});

// Edge Cases

it('should return 404 for non-existent appointment', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    postJson('/api/v1/doctor-ratings/99999', [
        'rating' => 5,
    ])
        ->assertNotFound();
});

it('should accept all valid ratings from 1 to 5', function (): void {
    foreach (range(1, 5) as $rating) {
        $receptor    = User::factory()->receptor()->create();
        $appointment = createCompletedAppointment($receptor);

        actingAs($receptor);

        postJson("/api/v1/doctor-ratings/{$appointment->id}", [
            'rating' => $rating,
        ])
            ->assertCreated()
            ->assertJsonPath('data.rating', $rating);
    }
});
