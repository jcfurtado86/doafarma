<?php

declare(strict_types = 1);

use App\Models\Drug;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('should be accessible via GET /api/v1/drugs', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    getJson('/api/v1/drugs')
        ->assertOk();
});

it('should require authentication', function (): void {
    getJson('/api/v1/drugs')
        ->assertUnauthorized();
});

it('should return 200 and correct JSON structure for valid query', function (): void {
    $user = User::factory()->create();

    Drug::factory()->count(4)->create();

    $drug = Drug::factory()->create([
        'product_name' => 'Paracetamol 500mg',
        'substance'    => 'Paracetamol',
        'laboratory'   => 'EMS',
    ]);

    actingAs($user, 'sanctum');

    $response = getJson('/api/v1/drugs');

    $response->assertOk();
    $response->assertJsonCount(5, 'data');
    $response->assertJsonFragment([
        'id'           => $drug->id,
        'product_name' => $drug->product_name,
        'substance'    => $drug->substance,
        'laboratory'   => $drug->laboratory,
    ]);
    $response->assertJsonStructure([
        'data' => [['id', 'product_name', 'substance', 'laboratory']],
    ]);
});
