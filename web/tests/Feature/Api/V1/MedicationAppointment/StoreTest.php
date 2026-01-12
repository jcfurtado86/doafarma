<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\Doctor;
use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

// Happy Path Tests

it('should allow receptor to create appointment for confirmed request', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($receptor);

    $response = postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ]);

    $response->assertCreated();

    assertDatabaseHas('medication_appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'status'                => 'scheduled',
        'receptor_confirmed'    => false,
        'doctor_confirmed'      => false,
    ]);
});

it('should return 201 with appointment data including request and address', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($receptor);

    $response = postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'scheduled_date',
                'scheduled_time',
                'status',
                'receptor_confirmed',
                'doctor_confirmed',
                'created_at',
                'updated_at',
                'medication_request' => [
                    'id',
                    'status',
                    'medication_offering',
                ],
                'address' => [
                    'id',
                    'location_name',
                    'full_address',
                ],
            ],
        ])
        ->assertJsonPath('data.status', 'scheduled');
});

it('should use doctors first address automatically', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    $address1 = Address::factory()->create(['user_id' => $doctor->user->id]);
    Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($receptor);

    $response = postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.address.id', $address1->id);
});

it('should allow scheduling for today', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($receptor);

    $response = postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->toDateString(),
        'scheduled_time'        => '18:00',
    ]);

    $response->assertCreated();
});

// Validation Error Tests

it('should return validation error when medication_request_id is missing', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('medication_request_id');
});

it('should return validation error when medication_request_id does not exist', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => 99999,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('medication_request_id');
});

it('should return validation error when scheduled_date is missing', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->confirmed()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_time'        => '14:30',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_date');
});

it('should return validation error when scheduled_date is in the past', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->subDays(1)->toDateString(),
        'scheduled_time'        => '14:30',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_date');
});

it('should return validation error when scheduled_time is missing', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->confirmed()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_time');
});

it('should return validation error when scheduled_time has invalid format', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->confirmed()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '2:30 PM',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_time');
});

// Authorization Error Tests

it('should return 401 unauthorized for unauthenticated users', function (): void {
    $request = MedicationRequest::factory()->confirmed()->create();

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ])->assertUnauthorized();
});

it('should return 403 forbidden when doctor tries to create appointment', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($doctor->user);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ])->assertForbidden();
});

it('should return 403 when receptor tries to create appointment for another receptors request', function (): void {
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();
    $request   = MedicationRequest::factory()
        ->forReceptor($receptor1)
        ->confirmed()
        ->create();

    actingAs($receptor2);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ])->assertForbidden();
});

// Business Rule Error Tests

it('should return 422 when request is not confirmed (pending)', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->pending()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ])
        ->assertStatus(422)
        ->assertJson(['message' => 'Apenas solicitações confirmadas podem ter agendamento.']);
});

it('should return 422 when request is rejected', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->rejected()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ])
        ->assertStatus(422)
        ->assertJson(['message' => 'Apenas solicitações confirmadas podem ter agendamento.']);
});

it('should return 409 conflict when appointment already exists for request', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    // Create existing appointment
    MedicationAppointment::factory()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ])
        ->assertConflict()
        ->assertJson(['message' => 'Já existe um agendamento para esta solicitação.']);
});

it('should return 422 when doctor has no registered addresses', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    // Don't create any addresses for this doctor
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date'        => now()->addDays(3)->toDateString(),
        'scheduled_time'        => '14:30',
    ])
        ->assertStatus(422)
        ->assertJson(['message' => 'O médico não possui endereços cadastrados.']);
});
