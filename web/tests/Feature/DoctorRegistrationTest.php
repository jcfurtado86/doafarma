<?php

declare(strict_types = 1);

use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertTrue;

function fakeCrmApi(string $status = 'Ativo', ?string $name = 'Dr. João Silva', ?string $specialty = 'Cardiologia'): void
{
    Http::fake([
        '*consultacrm*' => Http::response([
            'item' => [[
                'nome'          => $name,
                'situacao'      => $status,
                'especialidade' => $specialty,
            ]],
        ]),
    ]);
}

function fakeCrmApiUnavailable(): void
{
    Http::fake([
        '*consultacrm*' => Http::response('Internal Server Error', 500),
    ]);
}

function fakeCrmApiNotFound(): void
{
    Http::fake([
        '*consultacrm*' => Http::response([
            'item' => [],
        ]),
    ]);
}

function validDoctorData(array $overrides = []): array
{
    return array_merge([
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'phone_number'          => '(96) 98765-4321',
        'crm'                   => '123456',
        'crm_uf'                => 'SP',
        'password'              => 'password',
        'password_confirmation' => 'password',
        'device_name'           => 'Test Device',
        'addresses'             => [
            [
                'location_name' => 'Clínica X',
                'full_address'  => 'Rua A, 123, Bairro B, Cidade C, Estado D',
                'complement'    => 'Sala 1',
                'cep'           => '12345-678',
            ],
        ],
        'terms_accepted' => true,
    ], $overrides);
}

beforeEach(function (): void {
    config([
        'services.consultacrm.key'                => 'test-api-key',
        'services.consultacrm.url'                => 'https://www.consultacrm.com.br/api/index.php',
        'services.consultacrm.cache_days_valid'   => 30,
        'services.consultacrm.cache_days_invalid' => 7,
        'services.consultacrm.timeout'            => 3,
    ]);
});

it('should be able to register a doctor with active CRM', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    assertDatabaseHas('users', [
        'name'         => 'John Doe',
        'email'        => 'test@example.com',
        'phone_number' => '96987654321',
        'role'         => 'doctor',
        'status'       => 'approved',
    ]);

    assertTrue(password_verify('password', (string) User::whereEmail('test@example.com')->first()->password));

    assertDatabaseHas('doctors', [
        'user_id'    => User::whereEmail('test@example.com')->first()->id,
        'crm'        => '123456',
        'crm_uf'     => 'SP',
        'crm_source' => 'consultacrm',
        'specialty'  => 'Cardiologia',
    ]);

    assertDatabaseHas('addresses', [
        'location_name' => 'Clínica X',
        'full_address'  => 'Rua A, 123, Bairro B, Cidade C, Estado D',
        'complement'    => 'Sala 1',
        'cep'           => '12345678',
    ]);

    assertDatabaseCount('users', 1);
    assertDatabaseCount('doctors', 1);
    assertDatabaseCount('addresses', 1);

    $doctor = User::whereEmail('test@example.com')->first()->doctor;
    expect($doctor->crm_verified_at)->not->toBeNull();
});

it('should return a valid token pair upon successful registration', function (): void {
    fakeCrmApi();

    $response = postJson(route('doctor.register'), validDoctorData());

    $response->assertCreated();
    $response->assertJsonStructure([
        'data' => ['user', 'access_token', 'refresh_token', 'expires_in', 'refresh_expires_in'],
        'message',
    ]);

    $token = $response->json('data.access_token');
    expect($token)->not->toBeEmpty();

    $user = User::whereEmail('test@example.com')->first();
    expect($user->tokens)->toHaveCount(2);
});

it('should return the correct user data upon successful registration', function (): void {
    fakeCrmApi();

    $response = postJson(route('doctor.register'), validDoctorData());

    $response->assertCreated();

    $userData = $response->json('data.user');

    expect($userData)
        ->toHaveKey('id')
        ->toHaveKey('name', 'John Doe')
        ->toHaveKey('email', 'test@example.com')
        ->toHaveKey('phone_number', '96987654321')
        ->toHaveKey('role', 'doctor')
        ->toHaveKey('status', 'approved');
});

it('should ensure that there is a relationship between the user and their doctor profile', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    $user   = User::whereEmail('test@example.com')->first();
    $doctor = $user->doctor;

    expect($doctor)
        ->not->toBeNull()
        ->and($doctor->user_id)->toBe($user->id)
        ->and($doctor->crm)->toBe('123456')
        ->and($doctor->crm_uf)->toBe('SP');
});

it('should ensure that there is a relationship between the user and their addresses', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    $user    = User::whereEmail('test@example.com')->first();
    $address = $user->addresses()->first();

    expect($address)
        ->not->toBeNull()
        ->and($address->user_id)->toBe($user->id)
        ->and($address->location_name)->toBe('Clínica X');
});

it('should be able to register without a complement and with multiple addresses', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData([
        'addresses' => [
            [
                'location_name' => 'Clínica X',
                'full_address'  => 'Rua A, 123, Bairro B, Cidade C, Estado D',
                'cep'           => '12345-678',
            ],
            [
                'location_name' => 'Clínica Y',
                'full_address'  => 'Rua B, 456, Bairro C, Cidade D, Estado E',
                'complement'    => 'Sala 2',
                'cep'           => '98765-432',
            ],
        ],
    ]))->assertCreated();

    $user = User::whereEmail('test@example.com')->first();

    expect($user->addresses)
        ->toHaveCount(2)
        ->and($user->addresses[0]->complement)->toBeNull()
        ->and($user->addresses[1]->complement)->toBe('Sala 2');

    assertDatabaseCount('users', 1);
    assertDatabaseCount('addresses', 2);
});

it('should reject registration when CRM is inactive', function (): void {
    fakeCrmApi('Inativo');

    postJson(route('doctor.register'), validDoctorData())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['crm']);

    assertDatabaseCount('users', 0);
    assertDatabaseCount('doctors', 0);
});

it('should reject registration when CRM is canceled', function (): void {
    fakeCrmApi('Cancelado');

    postJson(route('doctor.register'), validDoctorData())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['crm']);

    assertDatabaseCount('users', 0);
    assertDatabaseCount('doctors', 0);
});

it('should reject registration when CRM is suspended', function (): void {
    fakeCrmApi('Suspenso');

    postJson(route('doctor.register'), validDoctorData())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['crm']);

    assertDatabaseCount('users', 0);
    assertDatabaseCount('doctors', 0);
});

it('should set user status to pending when API times out', function (): void {
    Http::fake([
        '*consultacrm*' => function (): void {
            throw new Illuminate\Http\Client\ConnectionException('Connection timed out');
        },
    ]);

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    assertDatabaseHas('users', [
        'email'  => 'test@example.com',
        'role'   => 'doctor',
        'status' => 'pending',
    ]);
});

it('should set user status to pending when API returns 500', function (): void {
    fakeCrmApiUnavailable();

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    assertDatabaseHas('users', [
        'email'  => 'test@example.com',
        'role'   => 'doctor',
        'status' => 'pending',
    ]);
});

it('should set user status to pending when API key is missing', function (): void {
    config(['services.consultacrm.key' => null]);

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    assertDatabaseHas('users', [
        'email'  => 'test@example.com',
        'role'   => 'doctor',
        'status' => 'pending',
    ]);
});

it('should set user status to pending when CRM is not found', function (): void {
    fakeCrmApiNotFound();

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    assertDatabaseHas('users', [
        'email'  => 'test@example.com',
        'role'   => 'doctor',
        'status' => 'pending',
    ]);
});

it('should explicitly set role to doctor', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    $user = User::whereEmail('test@example.com')->first();
    expect($user->role)->toBe(App\Enums\UserRole::Doctor);
});

it('should accept 4-digit CRM', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['crm' => '1234']))
        ->assertCreated();

    assertDatabaseHas('doctors', ['crm' => '1234']);
});

it('should accept 10-digit CRM', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['crm' => '1234567890']))
        ->assertCreated();

    assertDatabaseHas('doctors', ['crm' => '1234567890']);
});

it('should reject 3-digit CRM', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['crm' => '123']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['crm']);

    assertDatabaseCount('users', 0);
});

it('should reject 11-digit CRM', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['crm' => '12345678901']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['crm']);

    assertDatabaseCount('users', 0);
});

it('should validate required fields', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'email',
            'phone_number',
            'crm',
            'crm_uf',
            'password',
            'device_name',
            'addresses',
            'terms_accepted',
        ]);

    postJson(route('doctor.register'), [
        'addresses' => [
            [],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'addresses.0.location_name',
            'addresses.0.full_address',
            'addresses.0.cep',
        ]);

    assertDatabaseCount('users', 0);
    assertDatabaseCount('doctors', 0);
    assertDatabaseCount('addresses', 0);
});

it('should validate email format', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['email' => 'invalid-email']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    assertDatabaseCount('users', 0);
});

it('should validate unique email', function (): void {
    User::factory()->create(['email' => 'test@example.com']);

    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    assertDatabaseCount('users', 1);
});

it('should validate phone number format', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['phone_number' => 'invalid-phone']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phone_number']);

    assertDatabaseCount('users', 0);
});

it('should validate password confirmation', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData([
        'password_confirmation' => 'different-password',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);

    assertDatabaseCount('users', 0);
});

it('should validate addresses array format', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['addresses' => 'not-an-array']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['addresses']);

    assertDatabaseCount('users', 0);
});

it('should validate required address fields', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['addresses' => [[]]]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'addresses.0.location_name',
            'addresses.0.full_address',
            'addresses.0.cep',
        ]);

    assertDatabaseCount('users', 0);
});

it('should validate CEP format', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData([
        'addresses' => [[
            'location_name' => 'Clínica X',
            'full_address'  => 'Rua A, 123',
            'complement'    => 'Sala 1',
            'cep'           => 'invalid-cep',
        ]],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['addresses.0.cep']);

    assertDatabaseCount('users', 0);
});

it('should validate CRM format and length', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['crm' => 'ABC123']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['crm']);

    postJson(route('doctor.register'), validDoctorData(['crm' => '12']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['crm']);

    assertDatabaseCount('users', 0);
});

it('should validate CRM UF is valid state', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['crm_uf' => 'XX']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['crm_uf']);

    assertDatabaseCount('users', 0);
});

it('should validate terms_accepted is true', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['terms_accepted' => false]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['terms_accepted']);

    assertDatabaseCount('users', 0);
});

it('should validate address has valid location name length', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData([
        'addresses' => [[
            'location_name' => str_repeat('a', 256),
            'full_address'  => 'Rua A, 123',
            'complement'    => 'Sala 1',
            'cep'           => '12345-678',
        ]],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['addresses.0.location_name']);

    assertDatabaseCount('users', 0);
});

it('should validate full_address is not empty', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData([
        'addresses' => [[
            'location_name' => 'Clínica X',
            'full_address'  => '',
            'complement'    => 'Sala 1',
            'cep'           => '12345-678',
        ]],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['addresses.0.full_address']);

    assertDatabaseCount('users', 0);
});

it('should not allow duplicate CRM numbers', function (): void {
    User::factory()->doctor(crm: '123456', crm_uf: 'SP')->create();

    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['crm']);

    assertDatabaseCount('users', 1);
});
