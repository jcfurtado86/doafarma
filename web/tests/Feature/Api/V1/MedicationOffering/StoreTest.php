<?php

declare(strict_types = 1);

use App\Models\Doctor;
use App\Models\Drug;
use App\Models\MedicationOffering;
use App\Models\User;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

it('should be accessible via POST /api/v1/medication-offerings', function (): void {
    $user = User::factory()->doctor()->create();
    $drug = Drug::factory()->create();

    $payload1 = [
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-12345',
        'expires_at' => Carbon::now()->addMonths(6)->toDateString(),
        'quantity'   => 10,
    ];

    $payload2 = [
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-67890',
        'expires_at' => Carbon::now()->addMonths(12)->toDateString(),
        'quantity'   => 20,
    ];

    actingAs($user, 'sanctum');
    postJson('/api/v1/medication-offerings', $payload1)
        ->assertCreated();

    postJson(route('api.v1.medication-offerings.post'), $payload2)
        ->assertCreated();

    assertDatabaseCount('medication_offerings', 2);
});

it('should return 201, create the offering and associate it with the authenticated doctor', function (): void {
    $doctor = Doctor::factory()->create();
    $drug   = Drug::factory()->create();

    $payload = [
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-12345',
        'expires_at' => Carbon::now()->addMonths(6)->toDateString(),
        'quantity'   => 10,
    ];

    actingAs($doctor->user, 'sanctum');

    $response = postJson(route('api.v1.medication-offerings.post'), $payload);

    $response->assertCreated();

    $offering = MedicationOffering::first();

    $response->assertJson([
        'data' => [
            'id'         => $offering->id,
            'lot_number' => 'LOT-12345',
            'expires_at' => $payload['expires_at'],
            'quantity'   => 10,
        ],
    ]);

    assertDatabaseHas('medication_offerings', [
        'doctor_id'  => $doctor->id,
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-12345',
        'quantity'   => 10,
        'expires_at' => $payload['expires_at'],
    ]);
});

it('should return the correct Content-Type header', function (): void {
    $user = User::factory()->doctor()->create();
    $drug = Drug::factory()->create();

    $payload = [
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-CT-1',
        'expires_at' => Carbon::now()->addMonths(3)->toDateString(),
        'quantity'   => 1,
    ];

    actingAs($user, 'sanctum');

    $response = postJson(route('api.v1.medication-offerings.post'), $payload);
    $response->assertCreated();

    $contentType = $response->baseResponse->headers->get('Content-Type');
    expect($contentType)->toContain('application/json');
});

it('should return a validation error if required fields are missing', function (): void {
    $user = User::factory()->doctor()->create();
    actingAs($user, 'sanctum');

    $response = postJson(route('api.v1.medication-offerings.post'), []);
    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors']);

    // Expect required fields to be present in errors
    $json = $response->json();
    expect(array_keys($json['errors']))->toContain('drug_id');
    expect(array_keys($json['errors']))->toContain('lot_number');
    expect(array_keys($json['errors']))->toContain('expires_at');
    expect(array_keys($json['errors']))->toContain('quantity');
});

it('should return a validation error if drug_id does not exist', function (): void {
    $user = User::factory()->doctor()->create();
    actingAs($user, 'sanctum');

    $payload = [
        'drug_id'    => 9999999, // non-existent
        'lot_number' => 'LOT-XYZ',
        'expires_at' => Carbon::now()->addMonths(1)->toDateString(),
        'quantity'   => 5,
    ];

    $response = postJson(route('api.v1.medication-offerings.post'), $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('drug_id');
});

it('should return a validation error for invalid data types or formats', function (): void {
    $user = User::factory()->doctor()->create();
    $drug = Drug::factory()->create();

    actingAs($user, 'sanctum');

    $payload = [
        'drug_id'    => 'not-an-integer',
        'lot_number' => '', // empty
        'expires_at' => 'not-a-date',
        'quantity'   => 'ten',
    ];

    $response = postJson(route('api.v1.medication-offerings.post'), $payload);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors'])
        ->assertJsonValidationErrors([
            'drug_id'    => 'The drug id field must be an integer.',
            'lot_number' => 'The lot number field is required.',
            'expires_at' => 'The expires at field must be a valid date.',
            'quantity'   => 'The quantity field must be an integer.',
        ]);
});

it('should return a validation error if quantity is not a positive integer', function (): void {
    $user = User::factory()->doctor()->create();
    $drug = Drug::factory()->create();

    actingAs($user, 'sanctum');

    // zero
    $payloadZero = [
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-ZERO',
        'expires_at' => Carbon::now()->addMonth()->toDateString(),
        'quantity'   => 0,
    ];
    postJson(route('api.v1.medication-offerings.post'), $payloadZero)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');

    // negative
    $payloadNegative = [
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-NEG',
        'expires_at' => Carbon::now()->addMonth()->toDateString(),
        'quantity'   => -5,
    ];
    postJson(route('api.v1.medication-offerings.post'), $payloadNegative)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
});

it('should return a validation error if expiration_date is in the past', function (): void {
    $user = User::factory()->doctor()->create();
    $drug = Drug::factory()->create();

    actingAs($user, 'sanctum');

    $payload = [
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-PAST',
        'expires_at' => Carbon::now()->subDay()->toDateString(), // past
        'quantity'   => 2,
    ];

    $response = postJson(route('api.v1.medication-offerings.post'), $payload);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('expires_at');
});

it('should return 401 unauthorized if user is not authenticated', function (): void {
    $drug = Drug::factory()->create();

    $payload = [
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-AUTH',
        'expires_at' => Carbon::now()->addMonth()->toDateString(),
        'quantity'   => 3,
    ];

    postJson(route('api.v1.medication-offerings.post'), $payload)
        ->assertUnauthorized();
});

it('should return 403 forbidden if an authenticated user is not a doctor', function (): void {
    $user = User::factory()->create(); // regular user (not doctor)
    $drug = Drug::factory()->create();

    actingAs($user, 'sanctum');

    $payload = [
        'drug_id'    => $drug->id,
        'lot_number' => 'LOT-FORBID',
        'expires_at' => Carbon::now()->addMonth()->toDateString(),
        'quantity'   => 1,
    ];

    postJson(route('api.v1.medication-offerings.post'), $payload)
        ->assertForbidden();
});
