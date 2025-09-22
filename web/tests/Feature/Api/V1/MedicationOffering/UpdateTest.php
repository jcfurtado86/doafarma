<?php

declare(strict_types = 1);

use App\Models\Doctor;
use App\Models\Drug;
use App\Models\MedicationOffering;
use App\Models\User;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

it('updates the medication offering when requested by the owning doctor', function (): void {
    $doctor = Doctor::factory()->create();
    $drug   = Drug::factory()->create();

    $offering = MedicationOffering::factory()->create([
        'doctor_id'  => $doctor->id,
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-ORIG',
        'expires_at' => Carbon::now()->addMonths(3)->toDateString(),
        'quantity'   => 5,
    ]);

    $payload = [
        'lot_number' => 'LOT-UPDATED',
        'expires_at' => Carbon::now()->addMonths(12)->toDateString(),
        'quantity'   => 20,
    ];

    actingAs($doctor->user);

    $response = putJson(
        '/api/v1/medication-offerings/' . $offering->id,
        $payload
    );

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'id'         => $offering->id,
            'lot_number' => 'LOT-UPDATED',
            'expires_at' => $payload['expires_at'],
            'quantity'   => 20,
        ],
    ]);

    assertDatabaseHas('medication_offerings', [
        'id'         => $offering->id,
        'doctor_id'  => $doctor->id,
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-UPDATED',
        'expires_at' => $payload['expires_at'],
        'quantity'   => 20,
    ]);
});

it('allows partial updates (only provided fields are changed)', function (): void {
    $doctor   = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->create([
        'doctor_id'  => $doctor->id,
        'lot_number' => 'LOT-PARTIAL',
        'expires_at' => Carbon::now()->addMonths(6)->toDateString(),
        'quantity'   => 10,
    ]);

    actingAs($doctor->user);

    $payload = ['quantity' => 1];

    $response = putJson(
        route('api.v1.medication-offerings.put', ['medicationOffering' => $offering->id]),
        $payload
    );

    $response->assertOk();
    $response->assertJsonFragment(['quantity' => 1]);

    assertDatabaseHas('medication_offerings', [
        'id'       => $offering->id,
        'quantity' => 1,
    ]);
});

it('returns a validation error for various invalid data payloads', function (array $invalidData, array | string $expectedErrors) {
    $doctor   = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);

    actingAs($doctor->user);

    $payload = array_merge([
        'lot_number' => 'VALID-LOT',
        'expires_at' => now()->addYear()->toDateString(),
        'quantity'   => 10,
    ], $invalidData);

    putJson(
        route('api.v1.medication-offerings.put', $offering),
        $payload
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors($expectedErrors);
})->with([
    'quantity is zero'         => [['quantity' => 0], 'quantity'],
    'quantity is negative'     => [['quantity' => -1], 'quantity'],
    'quantity is a string'     => [['quantity' => 'abc'], 'quantity'],
    'expires_at is in past'    => [['expires_at' => now()->subDay()->toDateString()], 'expires_at'],
    'expires_at is not a date' => [['expires_at' => 'not-a-date'], 'expires_at'],
]);

it('returns 401 if the user is not authenticated', function (): void {
    $offering = MedicationOffering::factory()->create();

    putJson(
        route('api.v1.medication-offerings.put', ['medicationOffering' => $offering->id]),
        ['quantity' => 2]
    )->assertUnauthorized();
});

it('returns 403 if an authenticated user is not a doctor', function (): void {
    $user     = User::factory()->create();
    $offering = MedicationOffering::factory()->create();

    actingAs($user);

    putJson(
        route('api.v1.medication-offerings.put', ['medicationOffering' => $offering->id]),
        ['quantity' => 2]
    )->assertForbidden();
});

it('returns 403 if an authenticated doctor is not the owner of the offering', function (): void {
    $ownerDoctor = Doctor::factory()->create();
    $otherDoctor = Doctor::factory()->create();

    $offering = MedicationOffering::factory()->create([
        'doctor_id' => $ownerDoctor->id,
        'quantity'  => 5,
    ]);

    actingAs($otherDoctor->user);

    putJson(
        route('api.v1.medication-offerings.put', ['medicationOffering' => $offering->id]),
        ['quantity' => 10]
    )->assertForbidden();
});
