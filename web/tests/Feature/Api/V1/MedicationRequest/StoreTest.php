<?php

declare(strict_types = 1);

use App\Models\Doctor;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

// Happy Path Tests

it('should allow a receptor to create a request for an available offering', function (): void {
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->available()->create();

    actingAs($receptor);

    $response = postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ]);

    $response->assertCreated();

    assertDatabaseHas('medication_requests', [
        'receptor_id'            => $receptor->id,
        'medication_offering_id' => $offering->id,
        'status'                 => 'pending',
    ]);
});

it('should return 201 with request data including offering details', function (): void {
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->available()->create();

    actingAs($receptor);

    $response = postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'created_at',
                'updated_at',
                'medication_offering' => [
                    'id',
                    'quantity',
                    'lot_number',
                    'expires_at',
                    'status',
                    'drug',
                    'doctor',
                ],
            ],
        ])
        ->assertJsonPath('data.status', 'pending');
});

it('should change the offering status to reserved after request creation', function (): void {
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->available()->create();

    actingAs($receptor);

    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ])->assertCreated();

    $offering->refresh();
    expect($offering->status)->toBe('reserved');
});

it('should return the correct Content-Type header', function (): void {
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->available()->create();

    actingAs($receptor);

    $response = postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ]);

    $response->assertCreated();
    $contentType = $response->baseResponse->headers->get('Content-Type');
    expect($contentType)->toContain('application/json');
});

// Validation Error Tests

it('should return validation error when medication_offering_id is missing', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    postJson('/api/v1/medication-requests', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('medication_offering_id');
});

it('should return validation error when medication_offering_id does not exist', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => 99999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('medication_offering_id');
});

it('should return validation error when medication_offering_id is not an integer', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => 'not-an-integer',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('medication_offering_id');
});

// Authorization Error Tests

it('should return 401 unauthorized for unauthenticated users', function (): void {
    $offering = MedicationOffering::factory()->available()->create();

    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ])->assertUnauthorized();
});

it('should return 403 forbidden when a doctor tries to create a request', function (): void {
    $doctor   = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->available()->create();

    actingAs($doctor->user);

    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ])->assertForbidden();
});

// Business Rule Error Tests

it('should return 409 conflict when requesting an already reserved offering', function (): void {
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create();

    actingAs($receptor);

    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ])
        ->assertConflict()
        ->assertJson([
            'message' => 'Esta oferta já foi reservada por outro usuário.',
        ]);
});

it('should return 409 conflict when same receptor tries to request same offering twice', function (): void {
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->available()->create();

    actingAs($receptor);

    // First request succeeds
    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ])->assertCreated();

    // Second request fails
    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ])->assertConflict();
});

// Edge Case Tests

it('should allow requesting an offering that was previously rejected', function (): void {
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();
    $offering  = MedicationOffering::factory()->available()->create();

    // First receptor requests
    actingAs($receptor1);
    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ])->assertCreated();

    // Simulate rejection: update request status and offering status
    $request = MedicationRequest::first();
    $request->update(['status' => 'rejected']);

    // Refresh offering from database and update status
    $offering->refresh();
    $offering->update(['status' => 'available']);

    // Verify the offering is indeed available
    $offering->refresh();
    expect($offering->status)->toBe('available');

    // Second receptor can now request
    actingAs($receptor2);
    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ])->assertCreated();

    expect(MedicationRequest::count())->toBe(2);
});
