<?php

declare(strict_types = 1);

use App\Models\Doctor;
use App\Models\MedicationOffering;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('returns only the medication offerings belonging to the authenticated doctor', function (): void {
    // ARRANGE
    $doctor      = Doctor::factory()->create();
    $otherDoctor = Doctor::factory()->create();

    $myOfferings    = MedicationOffering::factory()->count(3)->create(['doctor_id' => $doctor->id]);
    $otherOfferings = MedicationOffering::factory()->count(2)->create(['doctor_id' => $otherDoctor->id]);

    actingAs($doctor->user, 'sanctum');

    // ACT
    $response = getJson('/api/v1/medication-offerings');

    // ASSERT
    $response->assertOk();
    $response->assertJsonCount(3, 'data');
    $response->assertJsonStructure([
        'data'  => [['id', 'lot_number', 'expires_at', 'quantity']],
        'meta'  => ['total'],
        'links' => [],
    ]);

    $returnedIds   = array_column($response->json('data'), 'id');
    $myOfferingIds = $myOfferings->pluck('id')->toArray();

    expect($returnedIds)->toHaveCount(3)
        ->and($returnedIds)->toEqualCanonicalizing($myOfferingIds);
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    getJson(route('api.v1.medication-offerings.list'))
        ->assertUnauthorized();
});

it('should return 403 Forbidden for authenticated users who are not doctors', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    getJson(route('api.v1.medication-offerings.list'))
        ->assertForbidden();
});

it('should return an empty paginated collection when the doctor has no offerings', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user, 'sanctum');

    $response = getJson(route('api.v1.medication-offerings.list'));

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
    expect($response->json('meta.total'))->toBe(0);
});

it('should respect the per_page pagination parameter', function (): void {
    $doctor = Doctor::factory()->create();

    MedicationOffering::factory()->count(5)->create(['doctor_id' => $doctor->id]);

    actingAs($doctor->user, 'sanctum');

    $response = getJson(route('api.v1.medication-offerings.list') . '?per_page=2');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    expect((int) $response->json('meta.per_page'))->toBe(2);
    expect((int) $response->json('meta.last_page'))->toBe((int) ceil(5 / 2));
});

it('should return correct pagination metadata (total, current_page, etc.)', function (): void {
    $doctor = Doctor::factory()->create();

    MedicationOffering::factory()->count(7)->create(['doctor_id' => $doctor->id]);

    actingAs($doctor->user, 'sanctum');

    $response = getJson(route('api.v1.medication-offerings.list') . '?per_page=3&page=2');

    $response->assertOk();

    $meta = $response->json('meta');

    expect($meta)->toHaveKey('total');
    expect($meta)->toHaveKey('per_page');
    expect($meta)->toHaveKey('current_page');
    expect($meta)->toHaveKey('last_page');

    expect((int) $meta['total'])->toBe(7);
    expect((int) $meta['per_page'])->toBe(3);
    expect((int) $meta['current_page'])->toBe(2);
    expect((int) $meta['last_page'])->toBe((int) ceil(7 / 3));
});

it('should optionally include related drug information when requested', function (): void {
    $doctor = Doctor::factory()->create();

    $offering = MedicationOffering::factory()->create([
        'doctor_id' => $doctor->id,
    ]);

    actingAs($doctor->user, 'sanctum');

    $response = getJson(route('api.v1.medication-offerings.list') . '?include=drug');

    $response->assertOk();

    // if the resource supports includes, drug should be present in each item
    $response->assertJsonStructure([
        'data' => [['drug' => ['id', 'substance']]] ,
    ]);
});
