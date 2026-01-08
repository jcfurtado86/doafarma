<?php

declare(strict_types = 1);

use App\Models\Doctor;
use App\Models\Drug;
use App\Models\MedicationOffering;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('rejects unauthenticated users with 401', function (): void {
    getJson('/api/v1/medication-offerings/search?q=aspirin')
        ->assertUnauthorized();
});

it('rejects invalid token with 401', function (): void {
    getJson('/api/v1/medication-offerings/search?q=aspirin', [
        'Authorization' => 'Bearer invalid-token',
    ])
        ->assertUnauthorized();
});

it('allows authenticated receptor to access search', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson('/api/v1/medication-offerings/search?q=test')
        ->assertOk();
});

it('rejects authenticated doctor with 403', function (): void {
    $doctor = User::factory()->doctor()->create();

    actingAs($doctor);

    getJson('/api/v1/medication-offerings/search?q=aspirin')
        ->assertForbidden();
});

it('rejects user without receptor role with 403', function (): void {
    $doctor = User::factory()->doctor()->create();

    actingAs($doctor);

    getJson('/api/v1/medication-offerings/search?q=aspirin')
        ->assertForbidden();
});

it('rejects missing q parameter with 422', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson('/api/v1/medication-offerings/search')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q']);
});

it('rejects empty q parameter with 422', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson('/api/v1/medication-offerings/search?q=')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q']);
});

it('accepts valid search query', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson('/api/v1/medication-offerings/search?q=aspirin')
        ->assertOk();
});

it('accepts single character query', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    getJson('/api/v1/medication-offerings/search?q=a')
        ->assertOk();
});

it('matches product_name', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'Aspirina']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=Aspirina');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.drug.product_name', 'Aspirina');
});

it('matches substance', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['substance' => 'Ácido Acetilsalicílico']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=Ácido Acetilsalicílico');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.drug.substance', 'Ácido Acetilsalicílico');
});

it('matches partial product_name', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'Aspirina']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=Aspir');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('matches partial substance', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['substance' => 'Dipirona Sódica']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=Dipirona');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('search is case-insensitive for product_name', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'Aspirina']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=aspirina');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('search is case-insensitive for substance', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['substance' => 'Paracetamol']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=paracetamol');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('returns multiple matching offerings', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug1    = Drug::factory()->create(['product_name' => 'Paracetamol 500mg']);
    $drug2    = Drug::factory()->create(['product_name' => 'Paracetamol 750mg']);

    MedicationOffering::factory()->create(['drug_id' => $drug1->id, 'quantity' => 10]);
    MedicationOffering::factory()->create(['drug_id' => $drug2->id, 'quantity' => 5]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=para');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});

it('includes offerings with quantity greater than zero', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'TestMed']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 5,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=TestMed');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('excludes offerings with quantity equal to zero', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'EmptyMed']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 0,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=EmptyMed');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

it('returns only available offerings when mixed availability', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'MixedMed']);

    MedicationOffering::factory()->create(['drug_id' => $drug->id, 'quantity' => 10]);
    MedicationOffering::factory()->create(['drug_id' => $drug->id, 'quantity' => 0]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=MixedMed');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    expect($response->json('data.0.quantity'))->toBeGreaterThan(0);
});

it('results contain required offering fields', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'FieldTest']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=FieldTest');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            [
                'id',
                'quantity',
                'lot_number',
                'expires_at',
            ],
        ],
    ]);
});

it('results contain nested drug information', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'DrugInfoTest']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=DrugInfoTest');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            [
                'drug' => [
                    'id',
                    'product_name',
                    'substance',
                    'presentation',
                    'laboratory',
                ],
            ],
        ],
    ]);
});

it('results contain nested doctor information', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    $drug     = Drug::factory()->create(['product_name' => 'DoctorInfoTest']);
    MedicationOffering::factory()->create([
        'doctor_id' => $doctor->id,
        'drug_id'   => $drug->id,
        'quantity'  => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=DoctorInfoTest');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            [
                'doctor' => [
                    'id',
                    'name',
                ],
            ],
        ],
    ]);
});

it('returns empty array when no matches found', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=nonexistentmedication12345');

    $response->assertOk();
    $response->assertJson(['data' => []]);
});

it('handles query with special characters', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'Vitamina B12']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=' . urlencode('vitamina B12'));

    $response->assertOk();
});

it('handles query with accented characters', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['substance' => 'Ácido Fólico']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=Ácido');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('handles query with numeric content', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'Vitamina B12']);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=B12');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('returns all matching offerings for large result set', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create(['product_name' => 'CommonMed']);

    MedicationOffering::factory()->count(10)->create([
        'drug_id'  => $drug->id,
        'quantity' => 5,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=CommonMed');

    $response->assertOk();
    $response->assertJsonCount(10, 'data');
});

it('does not return offering when drug does not match query', function (): void {
    $receptor = User::factory()->receptor()->create();
    $drug     = Drug::factory()->create([
        'product_name' => 'Aspirina',
        'substance'    => 'Ácido Acetilsalicílico',
    ]);
    MedicationOffering::factory()->create([
        'drug_id'  => $drug->id,
        'quantity' => 10,
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-offerings/search?q=Paracetamol');

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});
