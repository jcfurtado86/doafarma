# CRUD de Endereços — API Backend (Issue #160) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a CRUD API for medical addresses (`GET/POST/PUT/DELETE /api/v1/addresses` + `PATCH /api/v1/addresses/{address}/default`), replacing the ad-hoc address creation that only happens at doctor registration. Along the way, fix a real data-loss bug where `uf`/`city`/`neighborhood`/`number` are collected by the mobile registration form but silently discarded before reaching the database, and redesign the address schema into granular columns matching market standards (ViaCEP/schema.org).

**Architecture:** Laravel single-action controllers + Actions pattern (mirrors the `MedicationOffering` module). "Default address" is modeled as a `default_address_id` FK on `User` (not a boolean flag on `Address`), which makes having two defaults for the same user structurally impossible instead of merely unlikely.

**Tech Stack:** Laravel 12, PHP 8.5, Pest (TDD, 100% coverage), PostgreSQL 17, React Native/Expo mobile client (TypeScript, Zustand, zod).

**Spec:** `docs/specs/issue-160-crud-enderecos-backend/2026-07-11-design.md` (symlink to `~/.claude/spec/doafarma/epic-3-autogestao-conta-enderecos/issue-160-crud-enderecos-backend/2026-07-11-design.md`)

---

## Task 1: Redesign the `addresses` table and `default_address_id` on `users`

**Context:** The project is not yet in production, so the existing `addresses` migration is edited directly instead of creating a new one. `default_address_id` on `users` must be a **separate, new** migration dated after the `addresses` table migration — `users` is created before `addresses` (`0001_01_01_000000_create_users_table.php` vs `2025_02_04_152148_create_addresses_table.php`), so the FK can't be declared until the `addresses` table exists.

**Files:**
- Modify: `web/database/migrations/2025_02_04_152148_create_addresses_table.php`
- Create: `web/database/migrations/2026_07_11_000000_add_default_address_id_to_users_table.php`
- Modify: `web/app/Models/Address.php`
- Modify: `web/app/Models/User.php`
- Modify: `web/database/factories/AddressFactory.php`

- [ ] **Step 1: Rewrite the `addresses` migration with granular columns**

Replace the full contents of `web/database/migrations/2025_02_04_152148_create_addresses_table.php`:

```php
<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label');
            $table->char('cep', 8);
            $table->char('uf', 2);
            $table->string('city');
            $table->string('neighborhood');
            $table->string('street');
            $table->string('number');
            $table->string('complement')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
```

- [ ] **Step 2: Create the `default_address_id` migration**

Create `web/database/migrations/2026_07_11_000000_add_default_address_id_to_users_table.php`:

```php
<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('default_address_id')
                ->nullable()
                ->after('id')
                ->constrained('addresses')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('default_address_id');
        });
    }
};
```

- [ ] **Step 3: Run migrations and confirm they apply cleanly**

Run: `cd web && php artisan migrate:fresh`
Expected: All migrations run without errors, including the two above.

- [ ] **Step 4: Update the `Address` model's fillable fields**

Modify `web/app/Models/Address.php` — replace the `$fillable` array:

```php
    #[\Override]
    protected $fillable = [
        'user_id',
        'label',
        'cep',
        'uf',
        'city',
        'neighborhood',
        'street',
        'number',
        'complement',
    ];
```

- [ ] **Step 5: Add the `default_address_id` fillable field and `defaultAddress` relation to `User`**

Modify `web/app/Models/User.php`. Add `'default_address_id'` to `$fillable` (after `'status_changed_by'`):

```php
    #[Override]
    protected $fillable = [
        'name',
        'email',
        'cpf',
        'cpf_hash',
        'role',
        'status',
        'status_changed_at',
        'status_changed_by',
        'default_address_id',
        'password',
        'phone_number',
        'terms_accepted',
        'terms_accepted_at',
    ];
```

Add this relation right after the existing `addresses()` method (after line 140):

```php
    /**
     * Get the user's default address.
     *
     * @return BelongsTo<Address, $this>
     */
    public function defaultAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'default_address_id');
    }
```

- [ ] **Step 6: Update `AddressFactory` with the new fields**

Replace the full contents of `web/database/factories/AddressFactory.php`:

```php
<?php

declare(strict_types = 1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label'        => fake()->company(),
            'cep'          => fake()->numerify('########'),
            'uf'           => fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR']),
            'city'         => fake()->city(),
            'neighborhood' => fake()->word(),
            'street'       => fake()->streetName(),
            'number'       => (string) fake()->buildingNumber(),
            'complement'   => fake()->optional()->secondaryAddress(),
        ];
    }
}
```

- [ ] **Step 7: Commit**

```bash
git add web/database/migrations/2025_02_04_152148_create_addresses_table.php web/database/migrations/2026_07_11_000000_add_default_address_id_to_users_table.php web/app/Models/Address.php web/app/Models/User.php web/database/factories/AddressFactory.php
git commit -m "feat(web): redesign addresses schema into granular columns"
```

---

## Task 2: Fix the doctor registration bug (uf/city/neighborhood/number silently discarded)

**Context:** `DoctorRegistrationRequest` currently only validates `location_name`, `full_address`, `complement`, `cep`. Since Laravel's `validated()` only returns fields present in the rules, any `uf`/`city`/`neighborhood`/`number` sent by the client are silently dropped before `CreateDoctorAction` persists the address. This task writes the regression test first (RED), then fixes the request and action (GREEN).

**Files:**
- Modify: `web/tests/Feature/DoctorRegistrationTest.php`
- Modify: `web/app/Http/Requests/Auth/DoctorRegistrationRequest.php`
- Modify: `web/app/Actions/Auth/CreateDoctorAction.php`

- [ ] **Step 1: Update `validDoctorData()` helper and existing assertions to the new field names (RED)**

In `web/tests/Feature/DoctorRegistrationTest.php`, replace the `validDoctorData()` function (lines 42-63):

```php
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
                'label'        => 'Clínica X',
                'cep'          => '12345-678',
                'uf'           => 'SP',
                'city'         => 'São Paulo',
                'neighborhood' => 'Centro',
                'street'       => 'Rua A',
                'number'       => '123',
                'complement'   => 'Sala 1',
            ],
        ],
        'terms_accepted' => true,
    ], $overrides);
}
```

Replace the `assertDatabaseHas('addresses', ...)` block in the first test (`'should be able to register a doctor with active CRM'`, around line 98-103):

```php
    assertDatabaseHas('addresses', [
        'label'        => 'Clínica X',
        'cep'          => '12345678',
        'uf'           => 'SP',
        'city'         => 'São Paulo',
        'neighborhood' => 'Centro',
        'street'       => 'Rua A',
        'number'       => '123',
        'complement'   => 'Sala 1',
    ]);
```

Replace the `it('should ensure that there is a relationship between the user and their addresses', ...)` test (lines 164-176):

```php
it('should ensure that there is a relationship between the user and their addresses', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    $user    = User::whereEmail('test@example.com')->first();
    $address = $user->addresses()->first();

    expect($address)
        ->not->toBeNull()
        ->and($address->user_id)->toBe($user->id)
        ->and($address->label)->toBe('Clínica X')
        ->and($address->uf)->toBe('SP')
        ->and($address->city)->toBe('São Paulo')
        ->and($address->neighborhood)->toBe('Centro')
        ->and($address->number)->toBe('123');
});
```

Replace the multi-address test (`'should be able to register without a complement and with multiple addresses'`, lines 178-206):

```php
it('should be able to register without a complement and with multiple addresses', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData([
        'addresses' => [
            [
                'label'        => 'Clínica X',
                'cep'          => '12345-678',
                'uf'           => 'SP',
                'city'         => 'São Paulo',
                'neighborhood' => 'Centro',
                'street'       => 'Rua A',
                'number'       => '123',
            ],
            [
                'label'        => 'Clínica Y',
                'cep'          => '98765-432',
                'uf'           => 'RJ',
                'city'         => 'Rio de Janeiro',
                'neighborhood' => 'Copacabana',
                'street'       => 'Rua B',
                'number'       => '456',
                'complement'   => 'Sala 2',
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
```

Replace the required-fields validation test (`'should validate required fields'`, lines 340-372) — update the expected address error keys:

```php
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
            'addresses.0.label',
            'addresses.0.cep',
            'addresses.0.uf',
            'addresses.0.city',
            'addresses.0.neighborhood',
            'addresses.0.street',
            'addresses.0.number',
        ]);

    assertDatabaseCount('users', 0);
    assertDatabaseCount('doctors', 0);
    assertDatabaseCount('addresses', 0);
});
```

Replace `'should validate CEP format'` (lines 442-457):

```php
it('should validate CEP format', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData([
        'addresses' => [[
            'label'        => 'Clínica X',
            'cep'          => 'invalid-cep',
            'uf'           => 'SP',
            'city'         => 'São Paulo',
            'neighborhood' => 'Centro',
            'street'       => 'Rua A',
            'number'       => '123',
            'complement'   => 'Sala 1',
        ]],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['addresses.0.cep']);

    assertDatabaseCount('users', 0);
});
```

Replace `'should validate required address fields'` (lines 428-440):

```php
it('should validate required address fields', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData(['addresses' => [[]]]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'addresses.0.label',
            'addresses.0.cep',
            'addresses.0.uf',
            'addresses.0.city',
            'addresses.0.neighborhood',
            'addresses.0.street',
            'addresses.0.number',
        ]);

    assertDatabaseCount('users', 0);
});
```

Replace `'should validate address has valid location name length'` (lines 493-508) — rename to `label`:

```php
it('should validate address has valid label length', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData([
        'addresses' => [[
            'label'        => str_repeat('a', 256),
            'cep'          => '12345-678',
            'uf'           => 'SP',
            'city'         => 'São Paulo',
            'neighborhood' => 'Centro',
            'street'       => 'Rua A',
            'number'       => '123',
            'complement'   => 'Sala 1',
        ]],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['addresses.0.label']);

    assertDatabaseCount('users', 0);
});
```

Replace `'should validate full_address is not empty'` (lines 510-525) — rename to `street`:

```php
it('should validate street is not empty', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData([
        'addresses' => [[
            'label'        => 'Clínica X',
            'cep'          => '12345-678',
            'uf'           => 'SP',
            'city'         => 'São Paulo',
            'neighborhood' => 'Centro',
            'street'       => '',
            'number'       => '123',
            'complement'   => 'Sala 1',
        ]],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['addresses.0.street']);

    assertDatabaseCount('users', 0);
});
```

Add a new regression test right after `'should ensure that there is a relationship between the user and their addresses'`:

```php
it('persists uf, city, neighborhood and number instead of discarding them', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    assertDatabaseHas('addresses', [
        'uf'           => 'SP',
        'city'         => 'São Paulo',
        'neighborhood' => 'Centro',
        'number'       => '123',
    ]);
});
```

Add a new test right after it, confirming the first address becomes the default:

```php
it('sets the first registered address as the user default_address_id', function (): void {
    fakeCrmApi();

    postJson(route('doctor.register'), validDoctorData())->assertCreated();

    $user    = User::whereEmail('test@example.com')->first();
    $address = $user->addresses()->first();

    expect($user->default_address_id)->toBe($address->id);
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd web && ./vendor/bin/pest --filter=DoctorRegistrationTest`
Expected: FAIL — validation errors for `addresses.0.uf`, `addresses.0.city`, etc. not returned; `assertDatabaseHas` failures for the renamed columns (which don't exist yet reflects Task 1, but since Task 1 already ran, the columns exist — the failures here are about `DoctorRegistrationRequest` still expecting `location_name`/`full_address`).

- [ ] **Step 3: Fix `DoctorRegistrationRequest` validation rules**

Modify `web/app/Http/Requests/Auth/DoctorRegistrationRequest.php` — replace the `rules()` method:

```php
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                      => ['required', 'string', 'max:255'],
            'email'                     => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone_number'              => ['required', 'string', 'min:10', 'max:11'],
            'crm'                       => ['required', 'string', 'min:4', 'max:10', 'regex:/^[0-9]{4,10}$/', 'unique:' . Doctor::class],
            'crm_uf'                    => ['required', 'string', 'size:2', new ValidUF()],
            'password'                  => ['required', 'confirmed', Password::defaults()],
            'device_name'               => ['required', 'string', 'max:255'],
            'addresses'                 => ['array', 'min:1'],
            'addresses.*.label'         => ['required', 'string', 'max:255'],
            'addresses.*.cep'           => ['required', 'string', 'size:8'],
            'addresses.*.uf'            => ['required', 'string', 'size:2', new ValidUF()],
            'addresses.*.city'          => ['required', 'string', 'max:255'],
            'addresses.*.neighborhood'  => ['required', 'string', 'max:255'],
            'addresses.*.street'        => ['required', 'string', 'max:255'],
            'addresses.*.number'        => ['required', 'string', 'max:20'],
            'addresses.*.complement'    => ['nullable', 'string', 'max:255'],
            'terms_accepted'            => ['required', 'accepted'],
        ];
    }
```

Replace the `prepareForValidation()` method to clean each address's `cep` (not just the top-level phone number):

```php
    /**
     * Prepare the data for validation.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone_number' => $this->cleanNumeric($this->input('phone_number')),
            'addresses'    => is_array($addresses = $this->input('addresses', []))
                ? array_map(function ($address) {
                    if (is_array($address) && isset($address['cep'])) {
                        $address['cep'] = $this->cleanNumeric($address['cep']);
                    }

                    return $address;
                }, $addresses)
                : [],
        ]);
    }
```

This method's body is unchanged from what's already in the file — only `rules()` changed in this step. If `prepareForValidation()` in the file already matches the block above, skip re-writing it.

- [ ] **Step 4: Set the first address as the user's default in `CreateDoctorAction`**

Modify `web/app/Actions/Auth/CreateDoctorAction.php` — replace the transaction closure body (inside `DB::transaction(function () use ($data, $crmResult, $status): User { ... })`):

```php
        return DB::transaction(function () use ($data, $crmResult, $status): User {
            $user = User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'role'              => 'doctor',
                'phone_number'      => $data['phone_number'],
                'password'          => Hash::make($data['password']),
                'status'            => $status,
                'terms_accepted'    => $data['terms_accepted'],
                'terms_accepted_at' => now(),
            ]);

            $user->doctor()->create([
                'crm'             => $data['crm'],
                'crm_uf'          => $data['crm_uf'],
                'crm_verified_at' => $crmResult->isApproved() ? now() : null,
                'crm_source'      => $crmResult->source,
                'specialty'       => $crmResult->specialty,
            ]);

            $addresses = $user->addresses()->createMany($data['addresses']);

            $user->update(['default_address_id' => $addresses->first()->id]);

            return $user;
        });
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `cd web && ./vendor/bin/pest --filter=DoctorRegistrationTest`
Expected: PASS — all tests green, including the two new regression tests.

- [ ] **Step 6: Commit**

```bash
git add web/tests/Feature/DoctorRegistrationTest.php web/app/Http/Requests/Auth/DoctorRegistrationRequest.php web/app/Actions/Auth/CreateDoctorAction.php
git commit -m "fix(web): stop discarding uf/city/neighborhood/number on doctor registration"
```

---

## Task 3: Align the mobile registration form with the new backend contract

**Context:** `stores/doctorRegistrationFormStore.ts` currently concatenates `street/number/neighborhood/city/uf` into a single `full_address` string and deletes the granular fields before submitting — a client-side workaround for the old flat backend schema. Now that the backend accepts granular fields, this workaround must be removed, not just renamed.

**Files:**
- Modify: `mobile/stores/doctorRegistrationFormStore.ts`
- Modify: `mobile/screens/DoctorRegistration/steps/DoctorAddressStep/index.tsx`

- [ ] **Step 1: Update the `DoctorRegistrationFormData` address shape**

In `mobile/stores/doctorRegistrationFormStore.ts`, replace the `addresses` field type (lines 20-30):

```typescript
  // final step
  addresses?: {
    label?: string;
    cep?: string;
    uf?: string;
    city?: string;
    neighborhood?: string;
    street?: string;
    number?: string;
    complement?: string;
  }[];
  terms_accepted?: boolean;
```

- [ ] **Step 2: Remove the concatenation/delete workaround in `submitDoctorRegistrationForm`**

In the same file, delete this block entirely (lines 94-107):

```typescript
      if (
        formData.addresses &&
        formData.addresses[0].full_address &&
        formData.addresses[0].number &&
        formData.addresses[0].neighborhood &&
        formData.addresses[0].city &&
        formData.addresses[0].uf
      ) {
        formData.addresses[0].full_address = `${formData.addresses[0].full_address}, ${formData.addresses[0].number} - ${formData.addresses[0].neighborhood}, ${formData.addresses[0].city} - ${formData.addresses[0].uf}`;
        delete formData.addresses[0].number;
        delete formData.addresses[0].neighborhood;
        delete formData.addresses[0].city;
        delete formData.addresses[0].uf;
      }
```

The `submitDoctorRegistrationForm` function body should now go directly from the `ddd`/`phone_number` merge to `formData.device_name = await getDeviceName();`.

- [ ] **Step 3: Rename the zod schema fields in `DoctorAddressStep`**

In `mobile/screens/DoctorRegistration/steps/DoctorAddressStep/index.tsx`, replace the `doctorAddressSchema` definition (lines 24-49):

```typescript
const doctorAddressSchema = z.object({
  addresses: z.array(
    z.object({
      label: z
        .string({ error: 'Nome do consultório é obrigatório' })
        .max(255, 'Nome do consultório não pode exceder 255 caracteres'),
      cep: z.string({ error: 'CEP é obrigatório' }).length(8, 'CRM deve ter exatamente 8 dígitos'),
      uf: z.string({ error: 'UF é obrigatório' }).max(255, 'UF não pode exceder 255 caracteres'),
      city: z
        .string({ error: 'Cidade é obrigatória' })
        .max(255, 'Cidade não pode exceder 255 caracteres'),
      neighborhood: z
        .string({ error: 'Bairro é obrigatório' })
        .max(255, 'Bairro não pode exceder 255 caracteres'),
      street: z
        .string({ error: 'Rua ou Avenida é obrigatória' })
        .max(255, 'Rua ou Avenida não pode exceder 255 caracteres'),
      number: z
        .string({ error: 'Número é obrigatório' })
        .max(255, 'Número não pode exceder 255 caracteres'),
      complement: z
        .string()
        .optional()
        .transform((val) => (val === '' ? undefined : val))
        .pipe(z.string().max(255, 'Complemento não pode exceder 255 caracteres').optional()),
    })
  ),
});
```

- [ ] **Step 4: Rename the field references in the JSX (`Controller` `name` props and `errors` lookups)**

In the same file, update every `Controller` block's `name` and matching `errors.addresses?.[0].*` reference:
- `name="addresses.0.location_name"` → `name="addresses.0.label"`, and `errors.addresses?.[0]?.location_name?.message` → `errors.addresses?.[0]?.label?.message`
- `name="addresses.0.full_address"` → `name="addresses.0.street"`, and `errors.addresses?.[0]?.full_address?.message` → `errors.addresses?.[0]?.street?.message`

All other field names (`cep`, `uf`, `city`, `neighborhood`, `number`, `complement`) are unchanged.

- [ ] **Step 5: Type-check the mobile app**

Run: `cd mobile && npm run check-types`
Expected: No errors. If any remain, they'll point at the exact remaining `full_address`/`location_name` references to fix (handled in Task 4 for the appointment-flow consumers).

- [ ] **Step 6: Commit**

```bash
git add mobile/stores/doctorRegistrationFormStore.ts mobile/screens/DoctorRegistration/steps/DoctorAddressStep/index.tsx
git commit -m "fix(mobile): stop concatenating address fields before registration submit"
```

---

## Task 4: Rename Address fields in the appointment-flow mobile consumers

**Context:** `types/medicationAppointment.ts` defines an `Address` interface with `location_name`/`full_address`, consumed by `AddressSelector`, `MedicationAppointmentCard`, and `CounterProposeModal` (all part of the already-shipped scheduling flow). Since the backend `AddressResource` now returns `label`/`street` instead, these consumers must be updated or they'll silently render `undefined`.

**Files:**
- Modify: `mobile/types/medicationAppointment.ts`
- Modify: `mobile/components/AddressSelector/index.tsx`
- Modify: `mobile/components/MedicationAppointmentCard/index.tsx`
- Modify: `mobile/components/CounterProposeModal/index.tsx`

- [ ] **Step 1: Update the `Address` interface**

In `mobile/types/medicationAppointment.ts`, replace lines 7-13:

```typescript
export interface Address {
  id: number;
  label: string;
  street: string;
  complement?: string;
  cep: string;
}
```

- [ ] **Step 2: Update `AddressSelector`**

In `mobile/components/AddressSelector/index.tsx`, replace the two `address.location_name` occurrences with `address.label`, and `address.full_address` with `address.street`:

```typescript
      {...a11y.radioButton(address.label, isSelected)}
```

```typescript
        <Text style={styles.addressName}>{address.label}</Text>
        <Text style={styles.addressText}>{address.street}</Text>
```

- [ ] **Step 3: Update `MedicationAppointmentCard`**

In `mobile/components/MedicationAppointmentCard/index.tsx`, replace lines 79-80:

```typescript
              <Text style={styles.addressName}>{appointment.address.label}</Text>
              <Text style={styles.addressText}>{appointment.address.street}</Text>
```

- [ ] **Step 4: Update `CounterProposeModal`**

In `mobile/components/CounterProposeModal/index.tsx`, replace line 162:

```typescript
          <Text style={styles.infoText}>Local atual: {currentAddress.label}</Text>
```

- [ ] **Step 5: Type-check and lint the mobile app**

Run: `cd mobile && npm run check-types && npm run lint`
Expected: No errors related to `Address`, `location_name`, or `full_address`.

- [ ] **Step 6: Commit**

```bash
git add mobile/types/medicationAppointment.ts mobile/components/AddressSelector/index.tsx mobile/components/MedicationAppointmentCard/index.tsx mobile/components/CounterProposeModal/index.tsx
git commit -m "refactor(mobile): rename Address fields to match new backend contract"
```

---

## Task 5: `AddressPolicy` with a dedicated policy test

**Files:**
- Create: `web/app/Policies/AddressPolicy.php`
- Test: `web/tests/Unit/Policies/AddressPolicyTest.php`

- [ ] **Step 1: Write the failing policy test**

Create `web/tests/Unit/Policies/AddressPolicyTest.php`:

```php
<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;
use App\Policies\AddressPolicy;

beforeEach(function (): void {
    $this->policy = new AddressPolicy();
});

it('allows any authenticated user to view any addresses list', function (): void {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeTrue();
});

it('allows any authenticated user to create an address', function (): void {
    $user = User::factory()->create();

    expect($this->policy->create($user))->toBeTrue();
});

it('allows a user to view their own address', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    expect($this->policy->view($user, $address))->toBeTrue();
});

it('denies a user from viewing another user\'s address', function (): void {
    $user        = User::factory()->create();
    $otherUser   = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    expect($this->policy->view($user, $otherAddress))->toBeFalse();
});

it('allows a user to update their own address', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    expect($this->policy->update($user, $address))->toBeTrue();
});

it('denies a user from updating another user\'s address', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    expect($this->policy->update($user, $otherAddress))->toBeFalse();
});

it('allows a user to delete their own address', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    expect($this->policy->delete($user, $address))->toBeTrue();
});

it('denies a user from deleting another user\'s address', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    expect($this->policy->delete($user, $otherAddress))->toBeFalse();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd web && ./vendor/bin/pest --filter=AddressPolicyTest`
Expected: FAIL with "Class App\Policies\AddressPolicy not found".

- [ ] **Step 3: Implement `AddressPolicy`**

Create `web/app/Policies/AddressPolicy.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Address $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Address $address): bool
    {
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Address $address): bool
    {
        return $user->id === $address->user_id;
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `cd web && ./vendor/bin/pest --filter=AddressPolicyTest`
Expected: PASS, all 8 tests green.

- [ ] **Step 5: Commit**

```bash
git add web/app/Policies/AddressPolicy.php web/tests/Unit/Policies/AddressPolicyTest.php
git commit -m "feat(web): add AddressPolicy"
```

---

## Task 6: Update both `AddressResource` classes

**Context:** Two `AddressResource` classes exist — `App\Http\Resources\Api\V1\AddressResource` (used by the new CRUD and by `MedicationAppointmentResource`) and the legacy `App\Http\Resources\AddressResource` (used by the doctor registration response, via `RegistrationResource` → `UserResource`). Both are in active use and must be updated together.

**Files:**
- Modify: `web/app/Http/Resources/Api/V1/AddressResource.php`
- Modify: `web/app/Http/Resources/AddressResource.php`

- [ ] **Step 1: Update the V1 `AddressResource`**

Replace the full contents of `web/app/Http/Resources/Api/V1/AddressResource.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Address
 */
class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'label'        => $this->label,
            'cep'          => $this->cep,
            'uf'           => $this->uf,
            'city'         => $this->city,
            'neighborhood' => $this->neighborhood,
            'street'       => $this->street,
            'number'       => $this->number,
            'complement'   => $this->complement,
            'is_default'   => $request->user() !== null && $this->id === $request->user()->default_address_id,
        ];
    }
}
```

- [ ] **Step 2: Update the legacy `AddressResource`**

Replace the full contents of `web/app/Http/Resources/AddressResource.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Address
 */
class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'label'        => $this->label,
            'cep'          => $this->cep,
            'uf'           => $this->uf,
            'city'         => $this->city,
            'neighborhood' => $this->neighborhood,
            'street'       => $this->street,
            'number'       => $this->number,
            'complement'   => $this->complement,
            'is_default'   => $request->user() !== null && $this->id === $request->user()->default_address_id,
        ];
    }
}
```

- [ ] **Step 3: Run the full doctor registration test to confirm the resource change doesn't break the registration response**

Run: `cd web && ./vendor/bin/pest --filter=DoctorRegistrationTest`
Expected: PASS (still green from Task 2).

- [ ] **Step 4: Commit**

```bash
git add web/app/Http/Resources/Api/V1/AddressResource.php web/app/Http/Resources/AddressResource.php
git commit -m "feat(web): expose granular address fields and computed is_default in AddressResource"
```

---

## Task 7: `GET /api/v1/addresses` (List)

**Files:**
- Create: `web/app/Http/Controllers/Api/V1/Address/ListController.php`
- Create: `web/app/Http/Requests/Api/V1/Address/ListAddressRequest.php`
- Create: `web/app/Actions/Address/ListAddressAction.php`
- Modify: `web/routes/api.php`
- Test: `web/tests/Feature/Api/V1/Address/ListTest.php`

- [ ] **Step 1: Write the failing test (route exists + behavior)**

Create `web/tests/Feature/Api/V1/Address/ListTest.php`:

```php
<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('should be accessible via GET /api/v1/addresses', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    getJson('/api/v1/addresses')->assertOk();
    getJson(route('api.v1.addresses.list'))->assertOk();
});

it('returns only the addresses belonging to the authenticated user', function (): void {
    $user      = User::factory()->create();
    $otherUser = User::factory()->create();

    $myAddresses    = Address::factory()->count(2)->create(['user_id' => $user->id]);
    $otherAddresses = Address::factory()->count(3)->create(['user_id' => $otherUser->id]);

    actingAs($user, 'sanctum');

    $response = getJson(route('api.v1.addresses.list'));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');

    $returnedIds = array_column($response->json('data'), 'id');
    expect($returnedIds)->toEqualCanonicalizing($myAddresses->pluck('id')->toArray());
});

it('returns an empty collection when the user has no addresses', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    $response = getJson(route('api.v1.addresses.list'));

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

it('marks the address matching default_address_id as is_default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    $response = getJson(route('api.v1.addresses.list'));

    $response->assertOk();
    $response->assertJson([
        'data' => [
            ['id' => $address->id, 'is_default' => true],
        ],
    ]);
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    getJson(route('api.v1.addresses.list'))->assertUnauthorized();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd web && ./vendor/bin/pest --filter=Address/ListTest`
Expected: FAIL — route `api.v1.addresses.list` not defined.

- [ ] **Step 3: Add the addresses route group**

In `web/routes/api.php`, add the import at the top (alongside the other `use` statements, alphabetically after `use App\Http\Controllers\Api\V1\Auth;`):

```php
use App\Http\Controllers\Api\V1\Address;
```

Add a new route group inside the authenticated middleware group (right after the `doctor-ratings` group, before the closing `});` on line 146):

```php

        Route::prefix('addresses')->group(function (): void {
            Route::get('/', Address\ListController::class)
                ->name('api.v1.addresses.list');
            Route::post('/', Address\StoreController::class)
                ->name('api.v1.addresses.store');
            Route::put('/{address}', Address\UpdateController::class)
                ->name('api.v1.addresses.update');
            Route::delete('/{address}', Address\DeleteController::class)
                ->name('api.v1.addresses.delete');
            Route::patch('/{address}/default', Address\SetDefaultController::class)
                ->name('api.v1.addresses.set-default');
        });
```

- [ ] **Step 4: Create `ListAddressAction`**

Create `web/app/Actions/Address/ListAddressAction.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListAddressAction
{
    /**
     * Execute the action.
     *
     * @return Collection<int, Address>
     */
    public function execute(User $user): Collection
    {
        return $user->addresses()->orderBy('id')->get();
    }
}
```

- [ ] **Step 5: Create `ListAddressRequest`**

Create `web/app/Http/Requests/Api/V1/Address/ListAddressRequest.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\Address;

use App\Models\Address;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Address::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
```

- [ ] **Step 6: Create `ListController`**

Create `web/app/Http/Controllers/Api/V1/Address/ListController.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\ListAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\ListAddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ListAddressRequest $request, ListAddressAction $action): AnonymousResourceCollection
    {
        $addresses = $action->execute($request->user());

        return AddressResource::collection($addresses);
    }
}
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `cd web && ./vendor/bin/pest --filter=Address/ListTest`
Expected: PASS, all 5 tests green.

- [ ] **Step 8: Commit**

```bash
git add web/routes/api.php web/app/Actions/Address/ListAddressAction.php web/app/Http/Requests/Api/V1/Address/ListAddressRequest.php web/app/Http/Controllers/Api/V1/Address/ListController.php web/tests/Feature/Api/V1/Address/ListTest.php
git commit -m "feat(web): add GET /api/v1/addresses"
```

---

## Task 8: `POST /api/v1/addresses` (Store)

**Files:**
- Create: `web/app/Http/Controllers/Api/V1/Address/StoreController.php`
- Create: `web/app/Http/Requests/Api/V1/Address/StoreAddressRequest.php`
- Create: `web/app/Actions/Address/StoreAddressAction.php`
- Test: `web/tests/Feature/Api/V1/Address/StoreTest.php`

- [ ] **Step 1: Write the failing test**

Create `web/tests/Feature/Api/V1/Address/StoreTest.php`:

```php
<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

function validAddressPayload(array $overrides = []): array
{
    return array_merge([
        'label'        => 'Consultório Centro',
        'cep'          => '12345-678',
        'uf'           => 'SP',
        'city'         => 'São Paulo',
        'neighborhood' => 'Centro',
        'street'       => 'Rua A',
        'number'       => '123',
        'complement'   => 'Sala 1',
    ], $overrides);
}

it('should be accessible via POST /api/v1/addresses', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    postJson('/api/v1/addresses', validAddressPayload())->assertCreated();
});

it('creates the address for the authenticated user and returns 201', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    $response = postJson(route('api.v1.addresses.store'), validAddressPayload());

    $response->assertCreated();
    $response->assertJson([
        'data' => [
            'label'        => 'Consultório Centro',
            'uf'           => 'SP',
            'city'         => 'São Paulo',
            'neighborhood' => 'Centro',
            'street'       => 'Rua A',
            'number'       => '123',
            'complement'   => 'Sala 1',
        ],
    ]);

    assertDatabaseHas('addresses', [
        'user_id' => $user->id,
        'label'   => 'Consultório Centro',
        'cep'     => '12345678',
    ]);

    assertDatabaseCount('addresses', 1);
});

it('sets the first address created as the user default', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    $response = postJson(route('api.v1.addresses.store'), validAddressPayload());

    $addressId = $response->json('data.id');
    expect($user->fresh()->default_address_id)->toBe($addressId);
    $response->assertJson(['data' => ['is_default' => true]]);
});

it('does not override the default when the user already has one', function (): void {
    $user            = User::factory()->create();
    $existingAddress = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $existingAddress->id]);

    actingAs($user, 'sanctum');

    $response = postJson(route('api.v1.addresses.store'), validAddressPayload());

    $response->assertCreated();
    $response->assertJson(['data' => ['is_default' => false]]);
    expect($user->fresh()->default_address_id)->toBe($existingAddress->id);
});

it('returns a validation error if required fields are missing', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    $response = postJson(route('api.v1.addresses.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['label', 'cep', 'uf', 'city', 'neighborhood', 'street', 'number']);
});

it('returns a validation error for an invalid uf', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    postJson(route('api.v1.addresses.store'), validAddressPayload(['uf' => 'XX']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['uf']);
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    postJson(route('api.v1.addresses.store'), validAddressPayload())->assertUnauthorized();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd web && ./vendor/bin/pest --filter=Address/StoreTest`
Expected: FAIL — route not defined (controller class doesn't exist yet).

- [ ] **Step 3: Create `StoreAddressAction`**

Create `web/app/Actions/Address/StoreAddressAction.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;
use App\Models\User;

class StoreAddressAction
{
    /**
     * Execute the action.
     *
     * @param array<string, mixed> $data validated payload
     */
    public function execute(User $user, array $data): Address
    {
        $address = $user->addresses()->create($data);

        if ($user->default_address_id === null) {
            $user->update(['default_address_id' => $address->id]);
        }

        return $address->fresh();
    }
}
```

- [ ] **Step 4: Create `StoreAddressRequest`**

Create `web/app/Http/Requests/Api/V1/Address/StoreAddressRequest.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\Address;

use App\Models\Address;
use App\Rules\ValidUF;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Address::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label'        => ['required', 'string', 'max:255'],
            'cep'          => ['required', 'string', 'size:8'],
            'uf'           => ['required', 'string', 'size:2', new ValidUF()],
            'city'         => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'street'       => ['required', 'string', 'max:255'],
            'number'       => ['required', 'string', 'max:20'],
            'complement'   => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cep' => $this->cleanNumeric($this->input('cep')),
        ]);
    }

    /**
     * Clean numeric values.
     */
    private function cleanNumeric(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return preg_replace('/\D/', '', $value) ?? '';
    }
}
```

- [ ] **Step 5: Create `StoreController`**

Create `web/app/Http/Controllers/Api/V1/Address/StoreController.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\StoreAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\StoreAddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreAddressRequest $request, StoreAddressAction $action): AddressResource | JsonResponse
    {
        $address = $action->execute($request->user(), $request->validated());

        return AddressResource::make($address)
            ->response()
            ->setStatusCode(201);
    }
}
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `cd web && ./vendor/bin/pest --filter=Address/StoreTest`
Expected: PASS, all 7 tests green.

- [ ] **Step 7: Commit**

```bash
git add web/app/Actions/Address/StoreAddressAction.php web/app/Http/Requests/Api/V1/Address/StoreAddressRequest.php web/app/Http/Controllers/Api/V1/Address/StoreController.php web/tests/Feature/Api/V1/Address/StoreTest.php
git commit -m "feat(web): add POST /api/v1/addresses"
```

---

## Task 9: `PUT /api/v1/addresses/{address}` (Update)

**Files:**
- Create: `web/app/Http/Controllers/Api/V1/Address/UpdateController.php`
- Create: `web/app/Http/Requests/Api/V1/Address/UpdateAddressRequest.php`
- Create: `web/app/Actions/Address/UpdateAddressAction.php`
- Test: `web/tests/Feature/Api/V1/Address/UpdateTest.php`

- [ ] **Step 1: Write the failing test**

Create `web/tests/Feature/Api/V1/Address/UpdateTest.php`:

```php
<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

it('should be accessible via PUT /api/v1/addresses/{address}', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    putJson("/api/v1/addresses/{$address->id}", ['label' => 'Novo nome'])->assertOk();
});

it('updates only the provided fields and returns 200', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id, 'label' => 'Antigo']);

    actingAs($user, 'sanctum');

    $response = putJson(route('api.v1.addresses.update', $address), ['label' => 'Novo nome']);

    $response->assertOk();
    $response->assertJson(['data' => ['id' => $address->id, 'label' => 'Novo nome']]);

    assertDatabaseHas('addresses', ['id' => $address->id, 'label' => 'Novo nome']);
});

it('keeps is_default true after updating an address that was already the default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    $response = putJson(route('api.v1.addresses.update', $address), ['label' => 'Novo nome']);

    $response->assertOk();
    $response->assertJson(['data' => ['is_default' => true]]);
});

it('returns 403 when updating another user\'s address', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user, 'sanctum');

    putJson(route('api.v1.addresses.update', $otherAddress), ['label' => 'Hack'])
        ->assertForbidden();

    assertDatabaseHas('addresses', ['id' => $otherAddress->id, 'label' => $otherAddress->label]);
});

it('returns a validation error for an invalid uf', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    putJson(route('api.v1.addresses.update', $address), ['uf' => 'XX'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['uf']);
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    $address = Address::factory()->create();

    putJson(route('api.v1.addresses.update', $address), ['label' => 'Novo nome'])
        ->assertUnauthorized();
});

it('should return 404 Not Found for a non-existent address', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    putJson('/api/v1/addresses/99999', ['label' => 'Novo nome'])->assertNotFound();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd web && ./vendor/bin/pest --filter=Address/UpdateTest`
Expected: FAIL — route not defined (controller class doesn't exist yet).

- [ ] **Step 3: Create `UpdateAddressAction`**

Create `web/app/Actions/Address/UpdateAddressAction.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;

class UpdateAddressAction
{
    /**
     * Execute the action.
     *
     * @param array<string, mixed> $data validated payload
     */
    public function execute(Address $address, array $data): Address
    {
        $address->update($data);

        return $address->fresh();
    }
}
```

- [ ] **Step 4: Create `UpdateAddressRequest`**

Create `web/app/Http/Requests/Api/V1/Address/UpdateAddressRequest.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\Address;

use App\Rules\ValidUF;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('address'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label'        => ['sometimes', 'required', 'string', 'max:255'],
            'cep'          => ['sometimes', 'required', 'string', 'size:8'],
            'uf'           => ['sometimes', 'required', 'string', 'size:2', new ValidUF()],
            'city'         => ['sometimes', 'required', 'string', 'max:255'],
            'neighborhood' => ['sometimes', 'required', 'string', 'max:255'],
            'street'       => ['sometimes', 'required', 'string', 'max:255'],
            'number'       => ['sometimes', 'required', 'string', 'max:20'],
            'complement'   => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        if ($this->has('cep')) {
            $this->merge([
                'cep' => $this->cleanNumeric($this->input('cep')),
            ]);
        }
    }

    /**
     * Clean numeric values.
     */
    private function cleanNumeric(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return preg_replace('/\D/', '', $value) ?? '';
    }
}
```

- [ ] **Step 5: Create `UpdateController`**

Create `web/app/Http/Controllers/Api/V1/Address/UpdateController.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\UpdateAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\UpdateAddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use App\Models\Address;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        UpdateAddressRequest $request,
        Address $address,
        UpdateAddressAction $action
    ): AddressResource {
        $updatedAddress = $action->execute($address, $request->validated());

        return AddressResource::make($updatedAddress);
    }
}
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `cd web && ./vendor/bin/pest --filter=Address/UpdateTest`
Expected: PASS, all 7 tests green.

- [ ] **Step 7: Commit**

```bash
git add web/app/Actions/Address/UpdateAddressAction.php web/app/Http/Requests/Api/V1/Address/UpdateAddressRequest.php web/app/Http/Controllers/Api/V1/Address/UpdateController.php web/tests/Feature/Api/V1/Address/UpdateTest.php
git commit -m "feat(web): add PUT /api/v1/addresses/{address}"
```

---

## Task 10: `DELETE /api/v1/addresses/{address}` (Delete)

**Files:**
- Create: `web/app/Http/Controllers/Api/V1/Address/DeleteController.php`
- Create: `web/app/Http/Requests/Api/V1/Address/DeleteAddressRequest.php`
- Create: `web/app/Actions/Address/DeleteAddressAction.php`
- Test: `web/tests/Feature/Api/V1/Address/DeleteTest.php`

- [ ] **Step 1: Write the failing test**

Create `web/tests/Feature/Api/V1/Address/DeleteTest.php`:

```php
<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;

it('should be accessible via DELETE /api/v1/addresses/{address}', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    deleteJson("/api/v1/addresses/{$address->id}")->assertNoContent();
});

it('deletes the address and returns 204 for the owner', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertNoContent();

    assertDatabaseMissing('addresses', ['id' => $address->id]);
});

it('clears default_address_id when the deleted address was the default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertNoContent();

    expect($user->fresh()->default_address_id)->toBeNull();
});

it('allows deleting the last remaining address', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $address))->assertNoContent();

    expect($user->fresh()->addresses)->toHaveCount(0);
});

it('returns 403 when deleting another user\'s address', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user, 'sanctum');

    deleteJson(route('api.v1.addresses.delete', $otherAddress))->assertForbidden();

    expect(Address::find($otherAddress->id))->not->toBeNull();
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    $address = Address::factory()->create();

    deleteJson(route('api.v1.addresses.delete', $address))->assertUnauthorized();
});

it('should return 404 Not Found for a non-existent address', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    deleteJson('/api/v1/addresses/99999')->assertNotFound();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd web && ./vendor/bin/pest --filter=Address/DeleteTest`
Expected: FAIL — route not defined (controller class doesn't exist yet).

- [ ] **Step 3: Create `DeleteAddressAction`**

Create `web/app/Actions/Address/DeleteAddressAction.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;

class DeleteAddressAction
{
    /**
     * Execute the action.
     */
    public function execute(Address $address): void
    {
        $address->delete();
    }
}
```

- [ ] **Step 4: Create `DeleteAddressRequest`**

Create `web/app/Http/Requests/Api/V1/Address/DeleteAddressRequest.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Requests\Api\V1\Address;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('address'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
```

- [ ] **Step 5: Create `DeleteController`**

Create `web/app/Http/Controllers/Api/V1/Address/DeleteController.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\DeleteAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\DeleteAddressRequest;
use App\Models\Address;
use Illuminate\Http\Response;

class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(DeleteAddressRequest $request, Address $address, DeleteAddressAction $action): Response
    {
        $action->execute($address);

        return response()->noContent();
    }
}
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `cd web && ./vendor/bin/pest --filter=Address/DeleteTest`
Expected: PASS, all 7 tests green.

- [ ] **Step 7: Commit**

```bash
git add web/app/Actions/Address/DeleteAddressAction.php web/app/Http/Requests/Api/V1/Address/DeleteAddressRequest.php web/app/Http/Controllers/Api/V1/Address/DeleteController.php web/tests/Feature/Api/V1/Address/DeleteTest.php
git commit -m "feat(web): add DELETE /api/v1/addresses/{address}"
```

---

## Task 11: `PATCH /api/v1/addresses/{address}/default` (SetDefault)

**Files:**
- Create: `web/app/Http/Controllers/Api/V1/Address/SetDefaultController.php`
- Create: `web/app/Actions/Address/SetDefaultAddressAction.php`
- Test: `web/tests/Feature/Api/V1/Address/SetDefaultTest.php`

- [ ] **Step 1: Write the failing test**

Create `web/tests/Feature/Api/V1/Address/SetDefaultTest.php`:

```php
<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;

it('should be accessible via PATCH /api/v1/addresses/{address}/default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    actingAs($user, 'sanctum');

    patchJson("/api/v1/addresses/{$address->id}/default")->assertOk();
});

it('sets the address as the user default and returns is_default true', function (): void {
    $user         = User::factory()->create();
    $firstAddress = Address::factory()->create(['user_id' => $user->id]);
    $newDefault   = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $firstAddress->id]);

    actingAs($user, 'sanctum');

    $response = patchJson(route('api.v1.addresses.set-default', $newDefault));

    $response->assertOk();
    $response->assertJson(['data' => ['id' => $newDefault->id, 'is_default' => true]]);

    expect($user->fresh()->default_address_id)->toBe($newDefault->id);
});

it('is idempotent when the address is already the default', function (): void {
    $user    = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $user->update(['default_address_id' => $address->id]);

    actingAs($user, 'sanctum');

    patchJson(route('api.v1.addresses.set-default', $address))->assertOk();

    expect($user->fresh()->default_address_id)->toBe($address->id);
});

it('returns 403 when setting another user\'s address as default', function (): void {
    $user         = User::factory()->create();
    $otherUser    = User::factory()->create();
    $otherAddress = Address::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user, 'sanctum');

    patchJson(route('api.v1.addresses.set-default', $otherAddress))->assertForbidden();

    expect($otherUser->fresh()->default_address_id)->not->toBe($otherAddress->id);
});

it('should return 401 Unauthorized for unauthenticated users', function (): void {
    $address = Address::factory()->create();

    patchJson(route('api.v1.addresses.set-default', $address))->assertUnauthorized();
});

it('should return 404 Not Found for a non-existent address', function (): void {
    $user = User::factory()->create();

    actingAs($user, 'sanctum');

    patchJson('/api/v1/addresses/99999/default')->assertNotFound();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd web && ./vendor/bin/pest --filter=Address/SetDefaultTest`
Expected: FAIL — route not defined (controller class doesn't exist yet).

- [ ] **Step 3: Create `SetDefaultAddressAction`**

Create `web/app/Actions/Address/SetDefaultAddressAction.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;
use App\Models\User;

class SetDefaultAddressAction
{
    /**
     * Execute the action.
     */
    public function execute(User $user, Address $address): void
    {
        $user->update(['default_address_id' => $address->id]);
    }
}
```

- [ ] **Step 4: Create `SetDefaultController`**

Create `web/app/Http/Controllers/Api/V1/Address/SetDefaultController.php`:

```php
<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Address;

use App\Actions\Address\SetDefaultAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;

class SetDefaultController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Address $address, SetDefaultAddressAction $action): AddressResource
    {
        $this->authorize('update', $address);

        $action->execute($request->user(), $address);

        return AddressResource::make($address);
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `cd web && ./vendor/bin/pest --filter=Address/SetDefaultTest`
Expected: PASS, all 6 tests green.

- [ ] **Step 6: Commit**

```bash
git add web/app/Actions/Address/SetDefaultAddressAction.php web/app/Http/Controllers/Api/V1/Address/SetDefaultController.php web/tests/Feature/Api/V1/Address/SetDefaultTest.php
git commit -m "feat(web): add PATCH /api/v1/addresses/{address}/default"
```

---

## Task 12: Full verification

**Context:** Run the complete quality pipeline. Pre-existing unrelated failures (e.g. from the already-modified `app/Values/TokenName.php`) are not this issue's problem — only failures caused by this plan's changes need fixing here.

- [ ] **Step 1: Run the full backend test suite**

Run: `cd web && composer tp`
Expected: All tests pass, including every file touched or created above.

- [ ] **Step 2: Run PHPStan**

Run: `cd web && composer analyse`
Expected: No new errors introduced by the Address module, `User`/`Address` model changes, or the `DoctorRegistrationRequest`/`CreateDoctorAction` changes.

- [ ] **Step 3: Run Pint**

Run: `cd web && composer pint`
Expected: No formatting changes needed beyond what Pint auto-fixes; if it reformats files, review the diff and re-run tests.

- [ ] **Step 4: Run coverage check**

Run: `cd web && composer t:coverage -- --min=100`
Expected: 100% coverage maintained (no untested branch introduced by the new Address module).

- [ ] **Step 5: Run mobile type-check and lint one more time**

Run: `cd mobile && npm run check-types && npm run lint`
Expected: No errors.

- [ ] **Step 6: Run mobile tests**

Run: `cd mobile && npm test`
Expected: All existing tests pass (no test currently covers `DoctorAddressStep` or `AddressSelector` directly, based on the investigation in this plan — if any do, they must be updated to the new field names as part of this step).

- [ ] **Step 7: Commit any formatting fixes from Pint, if applicable**

```bash
git add -A
git commit -m "chore(web): apply Pint formatting"
```

(Skip this step if Pint made no changes.)
