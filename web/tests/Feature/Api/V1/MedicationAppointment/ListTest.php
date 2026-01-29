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

it('should return list of receptor appointments', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
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

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-appointments');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $appointment->id);
});

it('should return appointments with correct structure', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
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

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-appointments');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'scheduled_date',
                    'scheduled_time',
                    'status',
                    'receptor_confirmed',
                    'doctor_confirmed',
                    'medication_request',
                    'address',
                ],
            ],
        ]);
});

it('should return empty list when receptor has no appointments', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor, 'sanctum');

    getJson('/api/v1/medication-appointments')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('should only return receptors own appointments', function (): void {
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();
    $doctor    = Doctor::factory()->create();
    $address   = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering1 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering2 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request1 = MedicationRequest::factory()
        ->forReceptor($receptor1)
        ->forOffering($offering1)
        ->confirmed()
        ->create();
    $request2 = MedicationRequest::factory()
        ->forReceptor($receptor2)
        ->forOffering($offering2)
        ->confirmed()
        ->create();

    MedicationAppointment::factory()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address->id,
    ]);
    MedicationAppointment::factory()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address->id,
    ]);

    actingAs($receptor1, 'sanctum');

    getJson('/api/v1/medication-appointments')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('should filter appointments by status', function (): void {
    $receptor  = User::factory()->receptor()->create();
    $doctor    = Doctor::factory()->create();
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

    MedicationAppointment::factory()->proposed()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address->id,
    ]);
    MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address->id,
    ]);

    actingAs($receptor, 'sanctum');

    getJson('/api/v1/medication-appointments?status=proposed')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('should return appointments sorted by scheduled date', function (): void {
    $receptor  = User::factory()->receptor()->create();
    $doctor    = Doctor::factory()->create();
    $address   = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering1 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering2 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

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

    $apt1 = MedicationAppointment::factory()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address->id,
        'scheduled_date'        => now()->addDays(5)->toDateString(),
    ]);
    $apt2 = MedicationAppointment::factory()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address->id,
        'scheduled_date'        => now()->addDays(2)->toDateString(),
    ]);

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-appointments');

    $response->assertOk()
        ->assertJsonPath('data.0.id', $apt2->id)
        ->assertJsonPath('data.1.id', $apt1->id);
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    getJson('/api/v1/medication-appointments')
        ->assertUnauthorized();
});

it('should return 403 when doctor tries to access receptor list', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user, 'sanctum');

    getJson('/api/v1/medication-appointments')
        ->assertForbidden();
});
