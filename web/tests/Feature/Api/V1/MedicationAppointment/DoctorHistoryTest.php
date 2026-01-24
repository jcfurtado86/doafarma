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

it('should return list of completed donations (doctor history)', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
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

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-appointments/doctor-history');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $appointment->id)
        ->assertJsonPath('data.0.status', 'completed');
});

it('should return doctor history with correct structure including drug and receptor info', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-appointments/doctor-history');

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
                    'medication_request' => [
                        'id',
                        'status',
                        'receptor' => [
                            'id',
                            'name',
                        ],
                        'medication_offering' => [
                            'id',
                            'quantity',
                            'lot_number',
                            'expires_at',
                            'status',
                            'drug' => [
                                'id',
                                'product_name',
                                'substance',
                                'presentation',
                                'laboratory',
                            ],
                        ],
                    ],
                    'address',
                ],
            ],
        ]);
});

it('should return empty list when doctor has no completed donations', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user);

    getJson('/api/v1/medication-appointments/doctor-history')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('should only return completed appointments, not proposed or confirmed', function (): void {
    $receptor  = User::factory()->receptor()->create();
    $doctor    = Doctor::factory()->create();
    $address   = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering1 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering2 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering3 = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);

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
    $request3 = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering3)
        ->confirmed()
        ->create();

    // Proposed appointment - should NOT appear in history
    MedicationAppointment::factory()->proposed()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address->id,
    ]);
    // Confirmed appointment - should NOT appear in history
    MedicationAppointment::factory()->confirmed()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address->id,
    ]);
    // Completed appointment - should appear in history
    $completedAppointment = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request3->id,
        'address_id'            => $address->id,
    ]);

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-appointments/doctor-history');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $completedAppointment->id);
});

it('should only return own completed donations', function (): void {
    $receptor  = User::factory()->receptor()->create();
    $doctor1   = Doctor::factory()->create();
    $doctor2   = Doctor::factory()->create();
    $address1  = Address::factory()->create(['user_id' => $doctor1->user->id]);
    $address2  = Address::factory()->create(['user_id' => $doctor2->user->id]);
    $offering1 = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor1->id]);
    $offering2 = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor2->id]);

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

    $apt1 = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address1->id,
    ]);
    MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address2->id,
    ]);

    actingAs($doctor1->user);

    $response = getJson('/api/v1/medication-appointments/doctor-history');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $apt1->id);
});

it('should return history sorted by completion date (most recent first)', function (): void {
    $receptor  = User::factory()->receptor()->create();
    $doctor    = Doctor::factory()->create();
    $address   = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering1 = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
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

    // Older completed donation
    $apt1 = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address->id,
        'updated_at'            => now()->subDays(5),
    ]);
    // More recent completed donation
    $apt2 = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address->id,
        'updated_at'            => now()->subDays(2),
    ]);

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-appointments/doctor-history');

    $response->assertOk()
        ->assertJsonPath('data.0.id', $apt2->id)
        ->assertJsonPath('data.1.id', $apt1->id);
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    getJson('/api/v1/medication-appointments/doctor-history')
        ->assertUnauthorized();
});

it('should return 403 when receptor tries to access doctor history', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson('/api/v1/medication-appointments/doctor-history')
        ->assertForbidden();
});
