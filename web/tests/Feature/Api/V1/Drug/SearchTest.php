<?php

declare(strict_types = 1);

use App\Models\Drug;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('should be accessible via GET /api/v1/drugs/search', function (): void {
    $user = User::factory()->create();
    actingAs($user, 'sanctum');
    getJson('/api/v1/drugs/search')
        ->assertOk();

    getJson(route('api.v1.drugs.search'))
        ->assertOk();
});

it('should require authentication', function (): void {
    getJson('/api/v1/drugs/search')
        ->assertUnauthorized();

    $user = User::factory()->create();
    actingAs($user, 'sanctum');
    getJson('/api/v1/drugs/search')
        ->assertOk();
});

it('should return 200 and correct JSON structure for valid query', function (): void {
    $user = User::factory()->create();

    $matching1 = Drug::factory()->create([
        'product_name' => 'Paracetamol 500mg',
        'substance'    => 'Paracetamol',
        'laboratory'   => 'EMS',
    ]);

    $matching2 = Drug::factory()->create([
        'product_name' => 'Tylenol',
        'substance'    => 'Paracetamol',
        'laboratory'   => 'Johnson & Johnson',
    ]);

    $nonMatching = Drug::factory()->create([
        'product_name' => 'Ibuprofeno 400mg',
        'substance'    => 'Ibuprofeno',
        'laboratory'   => 'Ache',
    ]);

    actingAs($user, 'sanctum');

    $q        = 'Paracetamol';
    $response = getJson('/api/v1/drugs/search?q=' . urlencode($q));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'product_name', 'substance', 'laboratory'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta'  => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
        ]);

    $json = $response->json();

    $ids = array_column($json['data'], 'id');
    expect($ids)->toContain($matching1->id);
    expect($ids)->toContain($matching2->id);
    expect($ids)->not->toContain($nonMatching->id);
});

it('should return paginated results', function (): void {
    $user = User::factory()->create();

    $total = 25;
    Drug::factory()->count($total)->create();

    actingAs($user, 'sanctum');

    $perPage  = 10;
    $response = getJson('/api/v1/drugs/search?per_page=' . $perPage);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'product_name', 'substance', 'laboratory'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta'  => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
        ])
        ->assertJsonCount($perPage, 'data');

    $json = $response->json();

    expect($json['meta']['per_page'])->toBe($perPage);
    expect($json['meta']['total'])->toBe($total);
    expect($json['meta']['current_page'])->toBe(1);
    expect($json['meta']['last_page'])->toBe((int) ceil($total / $perPage));
    expect($json['links']['next'])->not->toBeNull();
});

it('should support sorting results', function (): void {
    $user = User::factory()->create();

    $a = Drug::factory()->create(['product_name' => 'Aspirina', 'substance' => 'Ácido acetilsalicílico', 'laboratory' => 'Lab A']);
    $c = Drug::factory()->create(['product_name' => 'Cataflam', 'substance' => 'Diclofenaco', 'laboratory' => 'Lab C']);
    $b = Drug::factory()->create(['product_name' => 'Ben-u-ron', 'substance' => 'Paracetamol', 'laboratory' => 'Lab B']);

    actingAs($user, 'sanctum');

    // ascending
    $respAsc = getJson('/api/v1/drugs/search?sort=product_name&order=asc');
    $respAsc->assertOk();
    $jsonAsc  = $respAsc->json();
    $namesAsc = array_column($jsonAsc['data'], 'product_name');
    expect($namesAsc)->toBe(['Aspirina', 'Ben-u-ron', 'Cataflam']);

    // descending
    $respDesc = getJson('/api/v1/drugs/search?sort=product_name&order=desc');
    $respDesc->assertOk();
    $jsonDesc  = $respDesc->json();
    $namesDesc = array_column($jsonDesc['data'], 'product_name');
    expect($namesDesc)->toBe(['Cataflam', 'Ben-u-ron', 'Aspirina']);
});

it('should return empty data if no matches found', function (): void {
    $user = User::factory()->create();

    // create some unrelated drugs
    Drug::factory()->create(['product_name' => 'Ibuprofeno 400mg', 'substance' => 'Ibuprofeno', 'laboratory' => 'Ache']);
    Drug::factory()->create(['product_name' => 'Cetirizina', 'substance' => 'Cetirizina', 'laboratory' => 'LabX']);

    actingAs($user, 'sanctum');

    $response = getJson('/api/v1/drugs/search?q=' . urlencode('TermoInexistente'));

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ])
        ->assertJsonCount(0, 'data');

    $json = $response->json();

    expect($json['meta']['total'])->toBe(0);
    expect($json['data'])->toBe([]);
});

it('should return validation error for invalid query params', function (): void {
    $user = User::factory()->create();
    actingAs($user, 'sanctum');

    $response = getJson('/api/v1/drugs/search?per_page=0&sort=unknown');

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors']);
});

it('should return correct Content-Type header', function (): void {
    $user = User::factory()->create();
    actingAs($user, 'sanctum');

    $response = getJson('/api/v1/drugs/search');
    $response->assertOk();

    $contentType = $response->baseResponse->headers->get('Content-Type');
    expect($contentType)->toContain('application/json');
});

it('should support partial matches in drug name', function (): void {
    $user = User::factory()->create();

    $match = Drug::factory()->create([
        'product_name' => 'Paracetamol 500mg',
        'substance'    => 'Paracetamol',
        'laboratory'   => 'EMS',
    ]);

    Drug::factory()->create([
        'product_name' => 'Ibuprofeno 400mg',
        'substance'    => 'Ibuprofeno',
        'laboratory'   => 'Ache',
    ]);

    actingAs($user, 'sanctum');

    $response = getJson('/api/v1/drugs/search?q=' . urlencode('acetamol'));
    $response->assertOk();

    $json = $response->json();
    $ids  = array_column($json['data'], 'id');

    expect($ids)->toContain($match->id);
});

it('should support multiple filters combined', function (): void {
    $user = User::factory()->create();

    $d1 = Drug::factory()->create([
        'product_name' => 'A X Paracetamol',
        'substance'    => 'Paracetamol',
        'laboratory'   => 'Lab1',
    ]);

    $d2 = Drug::factory()->create([
        'product_name' => 'B Paracetamol',
        'substance'    => 'Paracetamol',
        'laboratory'   => 'Lab2',
    ]);

    // unrelated
    Drug::factory()->create([
        'product_name' => 'C Outrol',
        'substance'    => 'Outra',
        'laboratory'   => 'Lab3',
    ]);

    actingAs($user, 'sanctum');

    $perPage   = 1;
    $baseQuery = http_build_query([
        'q'        => 'Paracetamol',
        'sort'     => 'product_name',
        'order'    => 'asc',
        'per_page' => $perPage,
    ]);

    // page 1 -> first item in ascending order
    $resp1 = getJson('/api/v1/drugs/search?' . $baseQuery);
    $resp1->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount($perPage, 'data');

    $json1 = $resp1->json();

    // per_page honored and total matches equal 2
    expect($json1['meta']['per_page'])->toBe($perPage);
    expect($json1['meta']['total'])->toBe(2);
    expect($json1['meta']['current_page'])->toBe(1);

    $firstNamesPage1 = array_column($json1['data'], 'product_name');
    expect($firstNamesPage1)->toBe(['A X Paracetamol']);
    expect($json1['links']['next'])->not->toBeNull();

    // page 2 -> second item in ascending order
    $resp2 = getJson('/api/v1/drugs/search?' . $baseQuery . '&page=2');
    $resp2->assertOk()
        ->assertJsonCount($perPage, 'data');

    $json2           = $resp2->json();
    $firstNamesPage2 = array_column($json2['data'], 'product_name');
    expect($json2['meta']['current_page'])->toBe(2);
    expect($firstNamesPage2)->toBe(['B Paracetamol']);
});

it('should respect per_page pagination parameter', function (): void {
    $user = User::factory()->create();
    actingAs($user, 'sanctum');

    $total = 12;
    Drug::factory()->count($total)->create();

    $perPage  = 5;
    $response = getJson('/api/v1/drugs/search?per_page=' . $perPage);

    $response->assertOk()
        ->assertJsonCount($perPage, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);

    $json = $response->json();

    expect($json['meta']['per_page'])->toBe($perPage);
    expect($json['meta']['total'])->toBe($total);
    expect($json['meta']['last_page'])->toBe((int) ceil($total / $perPage));
});

it('should return correct total count in pagination meta', function (): void {
    $user = User::factory()->create();
    actingAs($user, 'sanctum');

    // matching records
    Drug::factory()->count(12)->create(['product_name' => 'MatchMe', 'substance' => 'S', 'laboratory' => 'L']);
    // some non-matching records
    Drug::factory()->count(3)->create(['product_name' => 'NoMatch', 'substance' => 'X', 'laboratory' => 'L2']);

    $perPage = 5;
    $resp    = getJson('/api/v1/drugs/search?q=' . urlencode('MatchMe') . '&per_page=' . $perPage);
    $resp->assertOk();

    $json = $resp->json();

    expect($json['meta']['total'])->toBe(12);
    expect($json['meta']['per_page'])->toBe($perPage);
    expect($json['meta']['last_page'])->toBe((int) ceil(12 / $perPage));
});

it('should not leak sensitive fields', function (): void {
    $user = User::factory()->create();
    actingAs($user, 'sanctum');

    $drug = Drug::factory()->create([
        'product_name' => 'SecretDrug',
        'substance'    => 'SecretSub',
        'laboratory'   => 'SecretLab',
    ]);

    $resp = getJson('/api/v1/drugs/search?q=' . urlencode('SecretDrug'));
    $resp->assertOk();

    $json  = $resp->json();
    $first = $json['data'][0] ?? [];

    // ensure expected public fields exist
    expect($first)->toHaveKeys(['id', 'product_name', 'substance', 'laboratory']);

    // ensure internal/sensitive fields are not present
    expect(isset($first['created_at']))->toBeFalse();
    expect(isset($first['updated_at']))->toBeFalse();
    expect(isset($first['deleted_at']))->toBeFalse();
    // any other internal fields should also be absent (e.g., internal_code)
    expect(isset($first['internal_code']))->toBeFalse();
});

it('should support searching by active ingredient', function (): void {
    $user = User::factory()->create();

    $match = Drug::factory()->create([
        'product_name' => 'AlgumProduto',
        'substance'    => 'Dipirona',
        'laboratory'   => 'LabA',
    ]);

    Drug::factory()->create([
        'product_name' => 'OutroProduto',
        'substance'    => 'Ibuprofeno',
        'laboratory'   => 'LabB',
    ]);

    actingAs($user, 'sanctum');

    $resp = getJson('/api/v1/drugs/search?q=' . urlencode('dipirona'));
    $resp->assertOk();

    $json = $resp->json();
    $ids  = array_column($json['data'], 'id');

    expect($ids)->toContain($match->id);
});

it('should support searching by manufacturer', function (): void {
    $user = User::factory()->create();

    $match = Drug::factory()->create([
        'product_name' => 'M1',
        'substance'    => 'S1',
        'laboratory'   => 'ACME Labs',
    ]);

    // NOTE: controller currently searches product_name and substance only.
    // This test documents desired behavior (will fail until controller is extended to include laboratory).
    actingAs($user, 'sanctum');

    $resp = getJson('/api/v1/drugs/search?q=' . urlencode('ACME'));
    $resp->assertOk();

    $json = $resp->json();
    $ids  = array_column($json['data'], 'id');

    expect($ids)->toContain($match->id);
});

it('should support case-insensitive search', function (): void {
    $user = User::factory()->create();

    $match = Drug::factory()->create([
        'product_name' => 'PaRaCeTaMoL Super',
        'substance'    => 'PaRaCeTaMoL',
        'laboratory'   => 'LabCI',
    ]);

    actingAs($user, 'sanctum');

    $resp = getJson('/api/v1/drugs/search?q=' . urlencode('paracetamol'));
    $resp->assertOk();

    $json = $resp->json();
    $ids  = array_column($json['data'], 'id');

    expect($ids)->toContain($match->id);
});
//todo('should not return soft-deleted drugs');
