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

// Helper function to create a complete rating flow
function createRatingForDoctor(Doctor $doctor, User $receptor, int $rating = 5): DoctorRating
{
    $address     = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering    = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request     = MedicationRequest::factory()->forReceptor($receptor)->forOffering($offering)->confirmed()->create();
    $appointment = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);

    return DoctorRating::factory()->create([
        'medication_appointment_id' => $appointment->id,
        'doctor_id'                 => $doctor->id,
        'receptor_id'               => $receptor->id,
        'rating'                    => $rating,
    ]);
}

// Happy Path Tests

it('should return ratings for authenticated doctor', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    createRatingForDoctor($doctor, $receptor, 5);
    createRatingForDoctor($doctor, $receptor, 4);
    createRatingForDoctor($doctor, $receptor, 5);

    actingAs($doctor->user);

    $response = getJson('/api/v1/doctor-ratings/my-ratings');

    $response->assertOk()
        ->assertJsonStructure([
            'summary' => [
                'total_ratings',
                'average_rating',
                'rating_distribution' => [5, 4, 3, 2, 1],
            ],
            'ratings' => [
                '*' => [
                    'id',
                    'rating',
                    'comment',
                    'created_at',
                    'updated_at',
                    'receptor',
                ],
            ],
        ]);
});

it('should calculate correct summary statistics', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    // Create ratings with different values
    createRatingForDoctor($doctor, $receptor, 5);
    createRatingForDoctor($doctor, $receptor, 5);
    createRatingForDoctor($doctor, $receptor, 4);
    createRatingForDoctor($doctor, $receptor, 3);
    createRatingForDoctor($doctor, $receptor, 2);

    actingAs($doctor->user);

    $response = getJson('/api/v1/doctor-ratings/my-ratings');

    $response->assertOk()
        ->assertJsonPath('summary.total_ratings', 5)
        ->assertJsonPath('summary.average_rating', 3.8) // (5+5+4+3+2)/5 = 3.8
        ->assertJsonPath('summary.rating_distribution.5', 2)
        ->assertJsonPath('summary.rating_distribution.4', 1)
        ->assertJsonPath('summary.rating_distribution.3', 1)
        ->assertJsonPath('summary.rating_distribution.2', 1)
        ->assertJsonPath('summary.rating_distribution.1', 0);
});

it('should return empty ratings for doctor with no ratings', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user);

    $response = getJson('/api/v1/doctor-ratings/my-ratings');

    $response->assertOk()
        ->assertJsonPath('summary.total_ratings', 0)
        ->assertJsonPath('summary.average_rating', 0)
        ->assertJsonCount(0, 'ratings');
});

it('should return ratings ordered by most recent first', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    // Create old rating
    $oldAddress     = Address::factory()->create(['user_id' => $doctor->user->id]);
    $oldOffering    = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $oldRequest     = MedicationRequest::factory()->forReceptor($receptor)->forOffering($oldOffering)->confirmed()->create();
    $oldAppointment = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $oldRequest->id,
        'address_id'            => $oldAddress->id,
    ]);
    DoctorRating::factory()->create([
        'medication_appointment_id' => $oldAppointment->id,
        'doctor_id'                 => $doctor->id,
        'receptor_id'               => $receptor->id,
        'rating'                    => 3,
        'created_at'                => now()->subDays(5),
    ]);

    // Create new rating
    $newAddress     = Address::factory()->create(['user_id' => $doctor->user->id]);
    $newOffering    = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $newRequest     = MedicationRequest::factory()->forReceptor($receptor)->forOffering($newOffering)->confirmed()->create();
    $newAppointment = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $newRequest->id,
        'address_id'            => $newAddress->id,
    ]);
    DoctorRating::factory()->create([
        'medication_appointment_id' => $newAppointment->id,
        'doctor_id'                 => $doctor->id,
        'receptor_id'               => $receptor->id,
        'rating'                    => 5,
        'created_at'                => now(),
    ]);

    actingAs($doctor->user);

    $response = getJson('/api/v1/doctor-ratings/my-ratings');

    $response->assertOk()
        ->assertJsonPath('ratings.0.rating', 5)
        ->assertJsonPath('ratings.1.rating', 3);
});

it('should include receptor information', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create(['name' => 'Maria Santos']);

    createRatingForDoctor($doctor, $receptor, 5);

    actingAs($doctor->user);

    $response = getJson('/api/v1/doctor-ratings/my-ratings');

    $response->assertOk()
        ->assertJsonPath('ratings.0.receptor.name', 'Maria Santos');
});

it('should include medication appointment information', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    createRatingForDoctor($doctor, $receptor, 5);

    actingAs($doctor->user);

    $response = getJson('/api/v1/doctor-ratings/my-ratings');

    $response->assertOk()
        ->assertJsonStructure([
            'ratings' => [
                '*' => [
                    'medication_appointment',
                ],
            ],
        ]);
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    getJson('/api/v1/doctor-ratings/my-ratings')
        ->assertUnauthorized();
});

it('should return 403 for receptor users', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson('/api/v1/doctor-ratings/my-ratings')
        ->assertForbidden()
        ->assertJsonPath('message', 'Apenas médicos podem acessar suas avaliações.');
});

it('should only return ratings for the authenticated doctor', function (): void {
    $doctor1  = Doctor::factory()->create();
    $doctor2  = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    // Create rating for doctor1
    createRatingForDoctor($doctor1, $receptor, 5);
    createRatingForDoctor($doctor1, $receptor, 4);

    // Create rating for doctor2
    createRatingForDoctor($doctor2, $receptor, 3);

    actingAs($doctor1->user);

    $response = getJson('/api/v1/doctor-ratings/my-ratings');

    $response->assertOk()
        ->assertJsonPath('summary.total_ratings', 2)
        ->assertJsonCount(2, 'ratings');
});

// Edge Cases

it('should handle ratings with null comments', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    $address     = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering    = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request     = MedicationRequest::factory()->forReceptor($receptor)->forOffering($offering)->confirmed()->create();
    $appointment = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);

    DoctorRating::factory()->withoutComment()->create([
        'medication_appointment_id' => $appointment->id,
        'doctor_id'                 => $doctor->id,
        'receptor_id'               => $receptor->id,
        'rating'                    => 5,
    ]);

    actingAs($doctor->user);

    $response = getJson('/api/v1/doctor-ratings/my-ratings');

    $response->assertOk()
        ->assertJsonPath('ratings.0.comment', null);
});

it('should correctly round average to one decimal place', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    // Create ratings that result in a number that needs rounding
    // 4, 4, 3 = 11/3 = 3.666... should round to 3.7
    createRatingForDoctor($doctor, $receptor, 4);
    createRatingForDoctor($doctor, $receptor, 4);
    createRatingForDoctor($doctor, $receptor, 3);

    actingAs($doctor->user);

    $response = getJson('/api/v1/doctor-ratings/my-ratings');

    $response->assertOk()
        ->assertJsonPath('summary.average_rating', 3.7);
});
