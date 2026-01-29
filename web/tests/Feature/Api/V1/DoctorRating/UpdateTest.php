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
use function Pest\Laravel\patchJson;

// Helper function to create a rating scenario
function createRatingForUpdate(User $receptor, ?Doctor $doctor = null): DoctorRating
{
    $doctor ??= Doctor::factory()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);

    return DoctorRating::factory()->create([
        'medication_appointment_id' => $appointment->id,
        'doctor_id'                 => $doctor->id,
        'receptor_id'               => $receptor->id,
        'rating'                    => 3,
        'comment'                   => 'Original comment',
    ]);
}

// Happy Path Tests

it('should allow receptor to update rating', function (): void {
    $receptor = User::factory()->receptor()->create();
    $rating   = createRatingForUpdate($receptor);

    actingAs($receptor, 'sanctum');

    $response = patchJson("/api/v1/doctor-ratings/{$rating->id}", [
        'rating' => 5,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.rating', 5);

    assertDatabaseHas('doctor_ratings', [
        'id'     => $rating->id,
        'rating' => 5,
    ]);
});

it('should allow receptor to update comment', function (): void {
    $receptor = User::factory()->receptor()->create();
    $rating   = createRatingForUpdate($receptor);

    actingAs($receptor, 'sanctum');

    $response = patchJson("/api/v1/doctor-ratings/{$rating->id}", [
        'comment' => 'Updated comment',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.comment', 'Updated comment');

    assertDatabaseHas('doctor_ratings', [
        'id'      => $rating->id,
        'comment' => 'Updated comment',
    ]);
});

it('should allow receptor to update both rating and comment', function (): void {
    $receptor = User::factory()->receptor()->create();
    $rating   = createRatingForUpdate($receptor);

    actingAs($receptor, 'sanctum');

    $response = patchJson("/api/v1/doctor-ratings/{$rating->id}", [
        'rating'  => 4,
        'comment' => 'New comment',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.rating', 4)
        ->assertJsonPath('data.comment', 'New comment');
});

it('should allow removing comment by setting to null', function (): void {
    $receptor = User::factory()->receptor()->create();
    $rating   = createRatingForUpdate($receptor);

    actingAs($receptor, 'sanctum');

    $response = patchJson("/api/v1/doctor-ratings/{$rating->id}", [
        'comment' => null,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.comment', null);

    assertDatabaseHas('doctor_ratings', [
        'id'      => $rating->id,
        'comment' => null,
    ]);
});

// Validation Error Tests

it('should return 422 when rating is below 1', function (): void {
    $receptor = User::factory()->receptor()->create();
    $rating   = createRatingForUpdate($receptor);

    actingAs($receptor, 'sanctum');

    patchJson("/api/v1/doctor-ratings/{$rating->id}", [
        'rating' => 0,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['rating']);
});

it('should return 422 when rating is above 5', function (): void {
    $receptor = User::factory()->receptor()->create();
    $rating   = createRatingForUpdate($receptor);

    actingAs($receptor, 'sanctum');

    patchJson("/api/v1/doctor-ratings/{$rating->id}", [
        'rating' => 6,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['rating']);
});

it('should return 422 when comment exceeds max length', function (): void {
    $receptor = User::factory()->receptor()->create();
    $rating   = createRatingForUpdate($receptor);

    actingAs($receptor, 'sanctum');

    patchJson("/api/v1/doctor-ratings/{$rating->id}", [
        'comment' => str_repeat('a', 1001),
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['comment']);
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    $receptor = User::factory()->receptor()->create();
    $rating   = createRatingForUpdate($receptor);

    patchJson("/api/v1/doctor-ratings/{$rating->id}", [
        'rating' => 5,
    ])
        ->assertUnauthorized();
});

it('should return 403 when receptor tries to update another receptors rating', function (): void {
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();
    $rating    = createRatingForUpdate($receptor1);

    actingAs($receptor2, 'sanctum');

    patchJson("/api/v1/doctor-ratings/{$rating->id}", [
        'rating' => 5,
    ])
        ->assertForbidden();
});

it('should return 403 when doctor tries to update rating', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    $rating   = createRatingForUpdate($receptor, $doctor);

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/doctor-ratings/{$rating->id}", [
        'rating' => 5,
    ])
        ->assertForbidden();
});

// Edge Cases

it('should return 404 for non-existent rating', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor, 'sanctum');

    patchJson('/api/v1/doctor-ratings/99999', [
        'rating' => 5,
    ])
        ->assertNotFound();
});

it('should keep original values when updating with empty payload', function (): void {
    $receptor = User::factory()->receptor()->create();
    $rating   = createRatingForUpdate($receptor);

    actingAs($receptor, 'sanctum');

    $response = patchJson("/api/v1/doctor-ratings/{$rating->id}", []);

    $response->assertOk()
        ->assertJsonPath('data.rating', 3)
        ->assertJsonPath('data.comment', 'Original comment');
});
