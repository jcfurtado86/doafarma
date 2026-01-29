<?php

declare(strict_types = 1);

use App\Models\Doctor;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;

// Happy Path Tests

it('should allow doctor to confirm a pending request for their offering', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($doctor->user, 'sanctum');

    $response = patchJson("/api/v1/medication-requests/{$request->id}/confirm");

    $response->assertOk()
        ->assertJsonPath('data.status', 'confirmed');

    $request->refresh();
    expect($request->status)->toBe('confirmed');
});

it('should keep offering status as reserved after confirmation', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-requests/{$request->id}/confirm")
        ->assertOk();

    $offering->refresh();
    expect($offering->status)->toBe('reserved');
});

it('should return updated request data after confirmation', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($doctor->user, 'sanctum');

    $response = patchJson("/api/v1/medication-requests/{$request->id}/confirm");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'created_at',
                'updated_at',
                'medication_offering',
                'receptor',
            ],
        ])
        ->assertJsonPath('data.id', $request->id)
        ->assertJsonPath('data.status', 'confirmed');
});

// Authorization Error Tests

it('should return 401 unauthorized for unauthenticated users', function (): void {
    $request = MedicationRequest::factory()->pending()->create();

    patchJson("/api/v1/medication-requests/{$request->id}/confirm")
        ->assertUnauthorized();
});

it('should return 403 forbidden when receptor tries to confirm a request', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($receptor, 'sanctum');

    patchJson("/api/v1/medication-requests/{$request->id}/confirm")
        ->assertForbidden();
});

it('should return 403 forbidden when doctor tries to confirm another doctor\'s request', function (): void {
    $doctorA   = Doctor::factory()->create();
    $doctorB   = Doctor::factory()->create();
    $receptor  = User::factory()->receptor()->create();
    $offeringB = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctorB->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offeringB)
        ->pending()
        ->create();

    actingAs($doctorA->user, 'sanctum');

    patchJson("/api/v1/medication-requests/{$request->id}/confirm")
        ->assertForbidden();
});

// Business Rule Error Tests

it('should return 422 when trying to confirm an already confirmed request', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-requests/{$request->id}/confirm")
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Esta solicitação já foi processada.',
        ]);
});

it('should return 422 when trying to confirm a rejected request', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->available()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->rejected()
        ->create();

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-requests/{$request->id}/confirm")
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Esta solicitação já foi processada.',
        ]);
});

// Edge Cases

it('should return 404 for non-existent request', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user, 'sanctum');

    patchJson('/api/v1/medication-requests/99999/confirm')
        ->assertNotFound();
});
