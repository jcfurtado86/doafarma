<?php

declare(strict_types = 1);

use App\Enums\CrmStatus;
use App\Models\CrmRecord;
use App\Services\CrmValidationService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'services.consultacrm.key'                => 'test-api-key',
        'services.consultacrm.url'                => 'https://www.consultacrm.com.br/api/index.php',
        'services.consultacrm.cache_days_valid'   => 30,
        'services.consultacrm.cache_days_invalid' => 7,
        'services.consultacrm.timeout'            => 3,
    ]);
});

it('should return approved when API returns active CRM', function (): void {
    Http::fake([
        '*' => Http::response([
            'item' => [[
                'nome'          => 'Dr. João Silva',
                'situacao'      => 'Ativo',
                'especialidade' => 'Cardiologia',
            ]],
        ]),
    ]);

    $service = new CrmValidationService();
    $result  = $service->validate('123456', 'SP');

    expect($result->isApproved())->toBeTrue()
        ->and($result->isRejected())->toBeFalse()
        ->and($result->isPending())->toBeFalse()
        ->and($result->status)->toBe(CrmStatus::Active)
        ->and($result->doctorName)->toBe('Dr. João Silva')
        ->and($result->specialty)->toBe('Cardiologia')
        ->and($result->source)->toBe('consultacrm')
        ->and($result->apiAvailable)->toBeTrue();
});

it('should return rejected when API returns inactive CRM', function (): void {
    Http::fake([
        '*' => Http::response([
            'item' => [[
                'nome'     => 'Dr. João Silva',
                'situacao' => 'Inativo',
            ]],
        ]),
    ]);

    $service = new CrmValidationService();
    $result  = $service->validate('123456', 'SP');

    expect($result->isRejected())->toBeTrue()
        ->and($result->isApproved())->toBeFalse()
        ->and($result->status)->toBe(CrmStatus::Inactive);
});

it('should return rejected when API returns canceled CRM', function (): void {
    Http::fake([
        '*' => Http::response([
            'item' => [[
                'nome'     => 'Dr. João Silva',
                'situacao' => 'Cancelado',
            ]],
        ]),
    ]);

    $service = new CrmValidationService();
    $result  = $service->validate('123456', 'SP');

    expect($result->isRejected())->toBeTrue()
        ->and($result->status)->toBe(CrmStatus::Canceled);
});

it('should return rejected when API returns suspended CRM', function (): void {
    Http::fake([
        '*' => Http::response([
            'item' => [[
                'nome'     => 'Dr. João Silva',
                'situacao' => 'Suspenso',
            ]],
        ]),
    ]);

    $service = new CrmValidationService();
    $result  = $service->validate('123456', 'SP');

    expect($result->isRejected())->toBeTrue()
        ->and($result->status)->toBe(CrmStatus::Suspended);
});

it('should return pending when CRM is not found in API', function (): void {
    Http::fake([
        '*' => Http::response([
            'item' => [],
        ]),
    ]);

    $service = new CrmValidationService();
    $result  = $service->validate('999999', 'SP');

    expect($result->isPending())->toBeTrue()
        ->and($result->isApproved())->toBeFalse()
        ->and($result->isRejected())->toBeFalse()
        ->and($result->status)->toBe(CrmStatus::NotFound);
});

it('should return pending when API times out', function (): void {
    Http::fake([
        '*' => function (): void {
            throw new Illuminate\Http\Client\ConnectionException('Connection timed out');
        },
    ]);

    $service = new CrmValidationService();
    $result  = $service->validate('123456', 'SP');

    expect($result->isPending())->toBeTrue()
        ->and($result->apiAvailable)->toBeFalse()
        ->and($result->source)->toBe('unavailable');
});

it('should return pending when API returns 500', function (): void {
    Http::fake([
        '*' => Http::response('Internal Server Error', 500),
    ]);

    $service = new CrmValidationService();
    $result  = $service->validate('123456', 'SP');

    expect($result->isPending())->toBeTrue()
        ->and($result->apiAvailable)->toBeFalse();
});

it('should return pending when API returns 429', function (): void {
    Http::fake([
        '*' => Http::response('Rate Limited', 429),
    ]);

    $service = new CrmValidationService();
    $result  = $service->validate('123456', 'SP');

    expect($result->isPending())->toBeTrue()
        ->and($result->apiAvailable)->toBeFalse();
});

it('should return pending when API key is missing', function (): void {
    config(['services.consultacrm.key' => null]);

    $service = new CrmValidationService();
    $result  = $service->validate('123456', 'SP');

    expect($result->isPending())->toBeTrue()
        ->and($result->apiAvailable)->toBeFalse()
        ->and($result->source)->toBe('unavailable');
});

it('should return cached result when cache is not expired', function (): void {
    CrmRecord::create([
        'crm'          => '123456',
        'uf'           => 'SP',
        'doctor_name'  => 'Dr. Cached',
        'status'       => 'ativo',
        'specialties'  => ['Pediatria'],
        'source'       => 'consultacrm',
        'verified_at'  => now(),
        'expires_at'   => now()->addDays(30),
        'raw_response' => ['cached' => true],
    ]);

    Http::fake();

    $service = new CrmValidationService();
    $result  = $service->validate('123456', 'SP');

    expect($result->isApproved())->toBeTrue()
        ->and($result->doctorName)->toBe('Dr. Cached')
        ->and($result->specialty)->toBe('Pediatria');

    Http::assertNothingSent();
});

it('should re-fetch when cache is expired', function (): void {
    CrmRecord::create([
        'crm'          => '123456',
        'uf'           => 'SP',
        'doctor_name'  => 'Dr. Old',
        'status'       => 'ativo',
        'specialties'  => [],
        'source'       => 'consultacrm',
        'verified_at'  => now()->subDays(31),
        'expires_at'   => now()->subDay(),
        'raw_response' => null,
    ]);

    Http::fake([
        '*' => Http::response([
            'item' => [[
                'nome'          => 'Dr. Fresh',
                'situacao'      => 'Ativo',
                'especialidade' => 'Neurologia',
            ]],
        ]),
    ]);

    $service = new CrmValidationService();
    $result  = $service->validate('123456', 'SP');

    expect($result->doctorName)->toBe('Dr. Fresh')
        ->and($result->specialty)->toBe('Neurologia');

    Http::assertSentCount(1);
});

it('should store raw response in crm_records', function (): void {
    $apiResponse = [
        'item' => [[
            'nome'          => 'Dr. João Silva',
            'situacao'      => 'Ativo',
            'especialidade' => 'Cardiologia',
        ]],
    ];

    Http::fake([
        '*' => Http::response($apiResponse),
    ]);

    $service = new CrmValidationService();
    $service->validate('123456', 'SP');

    $record = CrmRecord::where('crm', '123456')->where('uf', 'SP')->first();

    expect($record)->not->toBeNull()
        ->and($record->raw_response)->toBe($apiResponse)
        ->and($record->status)->toBe('ativo')
        ->and($record->doctor_name)->toBe('Dr. João Silva')
        ->and($record->source)->toBe('consultacrm');
});
