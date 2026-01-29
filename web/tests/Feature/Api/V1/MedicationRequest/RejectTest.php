<?php

declare(strict_types = 1);

use App\Models\Doctor;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

// Happy Path Tests

it('should allow doctor to reject a pending request for their offering', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($doctor->user, 'sanctum');

    $response = patchJson("/api/v1/medication-requests/{$request->id}/reject");

    $response->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    $request->refresh();
    expect($request->status)->toBe('rejected');
});

it('should return offering status to available after rejection', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-requests/{$request->id}/reject")
        ->assertOk();

    $offering->refresh();
    expect($offering->status)->toBe('available');
});

it('should return updated request data after rejection', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($doctor->user, 'sanctum');

    $response = patchJson("/api/v1/medication-requests/{$request->id}/reject");

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
        ->assertJsonPath('data.status', 'rejected');
});

it('should allow another receptor to request after rejection', function (): void {
    $doctor    = Doctor::factory()->create();
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();
    $offering  = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor1)
        ->forOffering($offering)
        ->pending()
        ->create();

    // Doctor rejects
    actingAs($doctor->user, 'sanctum');
    patchJson("/api/v1/medication-requests/{$request->id}/reject")
        ->assertOk();

    // Another receptor can now request
    actingAs($receptor2, 'sanctum');
    postJson('/api/v1/medication-requests', [
        'medication_offering_id' => $offering->id,
    ])->assertCreated();

    expect(MedicationRequest::count())->toBe(2);
    expect(MedicationRequest::where('status', 'pending')->count())->toBe(1);
});

// Authorization Error Tests

it('should return 401 unauthorized for unauthenticated users', function (): void {
    $request = MedicationRequest::factory()->pending()->create();

    patchJson("/api/v1/medication-requests/{$request->id}/reject")
        ->assertUnauthorized();
});

it('should return 403 forbidden when receptor tries to reject a request', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->pending()
        ->create();

    actingAs($receptor, 'sanctum');

    patchJson("/api/v1/medication-requests/{$request->id}/reject")
        ->assertForbidden();
});

it('should return 403 forbidden when doctor tries to reject another doctor\'s request', function (): void {
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

    patchJson("/api/v1/medication-requests/{$request->id}/reject")
        ->assertForbidden();
});

// Business Rule Error Tests

it('should return 422 when trying to reject an already confirmed request', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-requests/{$request->id}/reject")
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Esta solicitação já foi processada.',
        ]);
});

it('should return 422 when trying to reject an already rejected request', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->available()->create(['doctor_id' => $doctor->id]);

    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->rejected()
        ->create();

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-requests/{$request->id}/reject")
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Esta solicitação já foi processada.',
        ]);
});

// Edge Cases

it('should return 404 for non-existent request', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user, 'sanctum');

    patchJson('/api/v1/medication-requests/99999/reject')
        ->assertNotFound();
});
