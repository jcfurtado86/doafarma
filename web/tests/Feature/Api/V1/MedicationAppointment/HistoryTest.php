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

it('should return list of completed appointments (medication history)', function (): void {
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

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-appointments/history');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $appointment->id)
        ->assertJsonPath('data.0.status', 'completed');
});

it('should return history with correct structure including drug and doctor info', function (): void {
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

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-appointments/history');

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
                            'doctor' => [
                                'id',
                                'name',
                            ],
                        ],
                    ],
                    'address',
                ],
            ],
        ]);
});

it('should return empty list when receptor has no completed appointments', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor, 'sanctum');

    getJson('/api/v1/medication-appointments/history')
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

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-appointments/history');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $completedAppointment->id);
});

it('should only return own completed appointments', function (): void {
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();
    $doctor    = Doctor::factory()->create();
    $address   = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering1 = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $offering2 = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);

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

    $apt1 = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address->id,
    ]);
    MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address->id,
    ]);

    actingAs($receptor1, 'sanctum');

    $response = getJson('/api/v1/medication-appointments/history');

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

    // Older completed appointment
    $apt1 = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request1->id,
        'address_id'            => $address->id,
        'updated_at'            => now()->subDays(5),
    ]);
    // More recent completed appointment
    $apt2 = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request2->id,
        'address_id'            => $address->id,
        'updated_at'            => now()->subDays(2),
    ]);

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-appointments/history');

    $response->assertOk()
        ->assertJsonPath('data.0.id', $apt2->id)
        ->assertJsonPath('data.1.id', $apt1->id);
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    getJson('/api/v1/medication-appointments/history')
        ->assertUnauthorized();
});

it('should return 403 when doctor tries to access history', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user, 'sanctum');

    getJson('/api/v1/medication-appointments/history')
        ->assertForbidden();
});

// Pagination Tests

it('should return paginated response with meta and links', function (): void {
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

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-appointments/history');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta'  => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
        ])
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.total', 1);
});

it('should return maximum 15 items per page', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);

    // Criar 20 appointments completos
    for ($i = 0; $i < 20; $i++) {
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
    }

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-appointments/history');

    $response->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.last_page', 2);
});

it('should return second page with page parameter', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);

    // Criar 20 appointments completos
    for ($i = 0; $i < 20; $i++) {
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
    }

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-appointments/history?page=2');

    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.current_page', 2);
});
