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

// Happy Path Tests

it('should list all ratings for a doctor', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    // Create 3 ratings for this doctor
    $ratings = collect();

    for ($i = 0; $i < 3; $i++) {
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

        $ratings->push(DoctorRating::factory()->create([
            'medication_appointment_id' => $appointment->id,
            'doctor_id'                 => $doctor->id,
            'receptor_id'               => $receptor->id,
            'rating'                    => 5 - $i,
        ]));
    }

    actingAs($receptor);

    $response = getJson("/api/v1/doctor-ratings/doctor/{$doctor->id}");

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('should return empty array when doctor has no ratings', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    $response = getJson("/api/v1/doctor-ratings/doctor/{$doctor->id}");

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('should return ratings ordered by most recent first', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    // Create old rating
    $address1     = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering1    = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request1     = MedicationRequest::factory()->forReceptor($receptor)->forOffering($offering1)->confirmed()->create();
    $appointment1 = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address1->id,
    ]);
    $oldRating = DoctorRating::factory()->create([
        'medication_appointment_id' => $appointment1->id,
        'doctor_id'                 => $doctor->id,
        'receptor_id'               => $receptor->id,
        'rating'                    => 3,
        'created_at'                => now()->subDays(5),
    ]);

    // Create new rating
    $address2     = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering2    = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request2     = MedicationRequest::factory()->forReceptor($receptor)->forOffering($offering2)->confirmed()->create();
    $appointment2 = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address2->id,
    ]);
    $newRating = DoctorRating::factory()->create([
        'medication_appointment_id' => $appointment2->id,
        'doctor_id'                 => $doctor->id,
        'receptor_id'               => $receptor->id,
        'rating'                    => 5,
        'created_at'                => now(),
    ]);

    actingAs($receptor);

    $response = getJson("/api/v1/doctor-ratings/doctor/{$doctor->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.rating', 5)
        ->assertJsonPath('data.1.rating', 3);
});

it('should include receptor name in response', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create(['name' => 'Maria Santos']);

    $address     = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering    = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request     = MedicationRequest::factory()->forReceptor($receptor)->forOffering($offering)->confirmed()->create();
    $appointment = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);

    DoctorRating::factory()->create([
        'medication_appointment_id' => $appointment->id,
        'doctor_id'                 => $doctor->id,
        'receptor_id'               => $receptor->id,
    ]);

    actingAs($receptor);

    $response = getJson("/api/v1/doctor-ratings/doctor/{$doctor->id}");

    $response->assertOk()
        ->assertJsonPath('data.0.receptor.name', 'Maria Santos');
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    $doctor = Doctor::factory()->create();

    getJson("/api/v1/doctor-ratings/doctor/{$doctor->id}")
        ->assertUnauthorized();
});

it('should allow doctors to view their own ratings', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user);

    getJson("/api/v1/doctor-ratings/doctor/{$doctor->id}")
        ->assertOk();
});

it('should allow receptors to view doctor ratings', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson("/api/v1/doctor-ratings/doctor/{$doctor->id}")
        ->assertOk();
});

// Edge Cases

it('should return 404 for non-existent doctor', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson('/api/v1/doctor-ratings/doctor/99999')
        ->assertNotFound();
});

it('should only show ratings for the specified doctor', function (): void {
    $doctor1  = Doctor::factory()->create();
    $doctor2  = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();

    // Create rating for doctor1
    $address1     = Address::factory()->create(['user_id' => $doctor1->user->id]);
    $offering1    = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor1->id]);
    $request1     = MedicationRequest::factory()->forReceptor($receptor)->forOffering($offering1)->confirmed()->create();
    $appointment1 = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address1->id,
    ]);
    DoctorRating::factory()->create([
        'medication_appointment_id' => $appointment1->id,
        'doctor_id'                 => $doctor1->id,
        'receptor_id'               => $receptor->id,
    ]);

    // Create rating for doctor2
    $address2     = Address::factory()->create(['user_id' => $doctor2->user->id]);
    $offering2    = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor2->id]);
    $request2     = MedicationRequest::factory()->forReceptor($receptor)->forOffering($offering2)->confirmed()->create();
    $appointment2 = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address2->id,
    ]);
    DoctorRating::factory()->create([
        'medication_appointment_id' => $appointment2->id,
        'doctor_id'                 => $doctor2->id,
        'receptor_id'               => $receptor->id,
    ]);

    actingAs($receptor);

    $response = getJson("/api/v1/doctor-ratings/doctor/{$doctor1->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});
