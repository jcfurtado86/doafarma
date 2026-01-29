<?php

declare(strict_types = 1);

use App\Models\MedicationOffering;
use App\Models\User;

test('authenticated users can view their own medication offering', function (): void {
    $user               = User::factory()->doctor()->create();
    $medicationOffering = MedicationOffering::factory()->create([
        'doctor_id' => $user->doctor->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/medication-offerings/{$medicationOffering->id}");

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'lot_number',
            'expires_at',
            'quantity',
            'drug' => [
                'id',
                'product_name',
                'substance',
                'laboratory',
            ],
        ],
    ]);
});

test('authenticated users cannot view other users medication offerings', function (): void {
    $user               = User::factory()->doctor()->create();
    $otherUser          = User::factory()->doctor()->create();
    $medicationOffering = MedicationOffering::factory()->create([
        'doctor_id' => $otherUser->doctor->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/medication-offerings/{$medicationOffering->id}");

    $response->assertForbidden();
});

test('unauthenticated users cannot view medication offerings', function (): void {
    $medicationOffering = MedicationOffering::factory()->create();

    $response = $this->getJson("/api/v1/medication-offerings/{$medicationOffering->id}");

    $response->assertUnauthorized();
});

test('returns 404 for non-existent medication offering', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/medication-offerings/999999');

    $response->assertNotFound();
});
