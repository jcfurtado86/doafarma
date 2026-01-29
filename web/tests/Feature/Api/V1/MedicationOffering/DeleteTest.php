<?php

declare(strict_types = 1);

use App\Models\Doctor;
use App\Models\MedicationOffering;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;

it('should delete the offering and return 204 No Content for the owner', function (): void {
    $doctor   = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);

    actingAs($doctor->user, 'sanctum');

    deleteJson("/api/v1/medication-offerings/{$offering->id}")
        ->assertNoContent();

    assertDatabaseMissing('medication_offerings', [
        'id' => $offering->id,
    ]);
});

it('should return 403 Forbidden when trying to delete another doctor\'s offering', function (): void {
    $doctor        = Doctor::factory()->create();
    $otherDoctor   = Doctor::factory()->create();
    $otherOffering = MedicationOffering::factory()->create(['doctor_id' => $otherDoctor->id]);

    actingAs($doctor->user, 'sanctum');

    deleteJson("/api/v1/medication-offerings/{$otherOffering->id}")
        ->assertForbidden();

    expect(MedicationOffering::find($otherOffering->id))->not->toBeNull();
});

it('should return 404 Not Found for a non-existent offering', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user, 'sanctum');

    deleteJson('/api/v1/medication-offerings/99999')
        ->assertNotFound();
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    $offering = MedicationOffering::factory()->create();

    deleteJson("/api/v1/medication-offerings/{$offering->id}")
        ->assertUnauthorized();
});

it('should return 403 Forbidden for authenticated users who are not doctors', function (): void {
    $user     = User::factory()->create();
    $offering = MedicationOffering::factory()->create();

    actingAs($user, 'sanctum');

    deleteJson("/api/v1/medication-offerings/{$offering->id}")
        ->assertForbidden();
});
