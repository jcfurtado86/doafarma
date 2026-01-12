<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\Doctor;
use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

// Happy Path Tests

it('should return list of doctor received appointments', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-appointments/received');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $appointment->id);
});

it('should include receptor details in response', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    MedicationAppointment::factory()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-appointments/received');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'medication_request' => [
                        'receptor' => ['id', 'name', 'email'],
                    ],
                ],
            ],
        ]);
});

it('should only return appointments for doctors offerings', function (): void {
    $doctorA  = Doctor::factory()->create();
    $doctorB  = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $addressA = Address::factory()->create(['user_id' => $doctorA->user->id]);
    $addressB = Address::factory()->create(['user_id' => $doctorB->user->id]);

    $offeringA = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctorA->id]);
    $offeringB = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctorB->id]);

    $requestA = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offeringA)
        ->confirmed()
        ->create();
    $requestB = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offeringB)
        ->confirmed()
        ->create();

    MedicationAppointment::factory()->create([
        'medication_request_id' => $requestA->id,
        'address_id'            => $addressA->id,
    ]);
    MedicationAppointment::factory()->create([
        'medication_request_id' => $requestB->id,
        'address_id'            => $addressB->id,
    ]);

    actingAs($doctorA->user);

    getJson('/api/v1/medication-appointments/received')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('should filter by status', function (): void {
    $doctor    = Doctor::factory()->create();
    $receptor  = User::factory()->receptor()->create();
    $address   = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering1 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering2 = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);

    $request1 = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering1)
        ->confirmed()
        ->create();
    $request2 = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering2)
        ->confirmed()
        ->create();

    MedicationAppointment::factory()->scheduled()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address->id,
    ]);
    MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address->id,
    ]);

    actingAs($doctor->user);

    getJson('/api/v1/medication-appointments/received?status=scheduled')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    getJson('/api/v1/medication-appointments/received')
        ->assertUnauthorized();
});

it('should return 403 when receptor tries to access doctor list', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson('/api/v1/medication-appointments/received')
        ->assertForbidden();
});
