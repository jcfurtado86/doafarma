<?php

declare(strict_types = 1);

use App\Models\Doctor;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

// Happy Path Tests

it('should allow a doctor to list requests for their offerings', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-requests/received');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'status',
                    'created_at',
                    'updated_at',
                    'receptor',
                    'medication_offering',
                ],
            ],
        ]);
});

it('should return empty array when doctor has no received requests', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-requests/received');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('should include receptor details in response', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-requests/received');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'receptor' => [
                        'id',
                        'name',
                        'email',
                        'phone_number',
                    ],
                ],
            ],
        ]);
});

it('should include offering and drug details in response', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-requests/received');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'medication_offering' => [
                        'id',
                        'quantity',
                        'drug',
                    ],
                ],
            ],
        ]);
});

it('should return requests ordered by created_at descending', function (): void {
    $doctor    = Doctor::factory()->create();
    $receptor  = User::factory()->receptor()->create();
    $offering1 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering2 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request1 = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering1)
        ->create(['created_at' => now()->subDay()]);

    $request2 = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering2)
        ->create(['created_at' => now()]);

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-requests/received');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($request2->id);
    expect($data[1]['id'])->toBe($request1->id);
});

// Filtering Tests

it('should filter requests by status=pending', function (): void {
    $doctor    = Doctor::factory()->create();
    $receptor  = User::factory()->receptor()->create();
    $offering1 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering2 = MedicationOffering::factory()->available()->create(['doctor_id' => $doctor->id]);

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering1)
        ->pending()
        ->create();

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering2)
        ->confirmed()
        ->create();

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-requests/received?status=pending');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'pending');
});

it('should filter requests by status=confirmed', function (): void {
    $doctor    = Doctor::factory()->create();
    $receptor  = User::factory()->receptor()->create();
    $offering1 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering2 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering1)
        ->pending()
        ->create();

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering2)
        ->confirmed()
        ->create();

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-requests/received?status=confirmed');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'confirmed');
});

it('should filter requests by status=rejected', function (): void {
    $doctor    = Doctor::factory()->create();
    $receptor  = User::factory()->receptor()->create();
    $offering1 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering2 = MedicationOffering::factory()->available()->create(['doctor_id' => $doctor->id]);

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering1)
        ->pending()
        ->create();

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering2)
        ->rejected()
        ->create();

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-requests/received?status=rejected');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'rejected');
});

it('should return all statuses when no filter is provided', function (): void {
    $doctor    = Doctor::factory()->create();
    $receptor  = User::factory()->receptor()->create();
    $offering1 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering2 = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $offering3 = MedicationOffering::factory()->available()->create(['doctor_id' => $doctor->id]);

    MedicationRequest::factory()->forReceptor($receptor)->forOffering($offering1)->pending()->create();
    MedicationRequest::factory()->forReceptor($receptor)->forOffering($offering2)->confirmed()->create();
    MedicationRequest::factory()->forReceptor($receptor)->forOffering($offering3)->rejected()->create();

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-requests/received');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

// Authorization Error Tests

it('should return 401 unauthorized for unauthenticated users', function (): void {
    getJson('/api/v1/medication-requests/received')
        ->assertUnauthorized();
});

it('should return 403 forbidden when receptor tries to access received endpoint', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson('/api/v1/medication-requests/received')
        ->assertForbidden();
});

// Data Isolation Test

it('should not allow doctor A to see doctor B\'s received requests', function (): void {
    $doctorA   = Doctor::factory()->create();
    $doctorB   = Doctor::factory()->create();
    $receptor  = User::factory()->receptor()->create();
    $offeringB = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctorB->id]);

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offeringB)
        ->pending()
        ->create();

    actingAs($doctorA->user);

    $response = getJson('/api/v1/medication-requests/received');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});
