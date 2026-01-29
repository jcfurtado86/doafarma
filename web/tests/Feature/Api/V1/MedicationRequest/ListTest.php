<?php

declare(strict_types = 1);

use App\Models\Doctor;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

// Happy Path Tests

it('should allow a receptor to list their own requests', function (): void {
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create();

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-requests');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'status',
                    'created_at',
                    'updated_at',
                    'medication_offering',
                ],
            ],
        ]);
});

it('should return empty array when receptor has no requests', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-requests');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('should include offering and drug details in response', function (): void {
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create();

    MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-requests');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
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
                        ],
                        'doctor' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ],
        ]);
});

it('should return requests ordered by created_at descending', function (): void {
    $receptor  = User::factory()->receptor()->create();
    $offering1 = MedicationOffering::factory()->reserved()->create();
    $offering2 = MedicationOffering::factory()->reserved()->create();

    $request1 = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering1)
        ->create(['created_at' => now()->subDay()]);

    $request2 = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering2)
        ->create(['created_at' => now()]);

    actingAs($receptor, 'sanctum');

    $response = getJson('/api/v1/medication-requests');

    $response->assertOk()
        ->assertJsonCount(2, 'data');

    $data = $response->json('data');
    expect($data[0]['id'])->toBe($request2->id);
    expect($data[1]['id'])->toBe($request1->id);
});

it('should only return the authenticated receptor\'s requests', function (): void {
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();
    $offering1 = MedicationOffering::factory()->reserved()->create();
    $offering2 = MedicationOffering::factory()->reserved()->create();

    MedicationRequest::factory()
        ->forReceptor($receptor1)
        ->forOffering($offering1)
        ->create();

    MedicationRequest::factory()
        ->forReceptor($receptor2)
        ->forOffering($offering2)
        ->create();

    actingAs($receptor1, 'sanctum');

    $response = getJson('/api/v1/medication-requests');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// Authorization Error Tests

it('should return 401 unauthorized for unauthenticated users', function (): void {
    getJson('/api/v1/medication-requests')
        ->assertUnauthorized();
});

it('should return empty array when doctor tries to list (no requests as receptor)', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user, 'sanctum');

    $response = getJson('/api/v1/medication-requests');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

// Data Isolation Test

it('should not allow receptor A to see receptor B\'s requests', function (): void {
    $receptorA = User::factory()->receptor()->create();
    $receptorB = User::factory()->receptor()->create();
    $offering  = MedicationOffering::factory()->reserved()->create();

    MedicationRequest::factory()
        ->forReceptor($receptorB)
        ->forOffering($offering)
        ->create();

    actingAs($receptorA, 'sanctum');

    $response = getJson('/api/v1/medication-requests');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});
