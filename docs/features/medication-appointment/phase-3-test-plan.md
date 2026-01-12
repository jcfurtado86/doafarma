# Phase 3: Test Plan - Medication Appointment System

## Overview

This document defines all test cases for the Medication Appointment System feature. Tests follow the existing project patterns using Pest PHP for backend testing.

---

## Testing Strategy

### Test Types

| Type | Framework | Coverage Goal |
|------|-----------|---------------|
| Feature/Integration Tests | Pest PHP | All API endpoints |
| Unit Tests | Pest PHP | Complex actions (optional) |
| Manual Tests | N/A | Bug fixes (redirects) |

### Test Organization

```
tests/Feature/Api/V1/MedicationAppointment/
├── StoreTest.php                    # POST /v1/medication-appointments
├── ListTest.php                     # GET /v1/medication-appointments
├── ReceivedTest.php                 # GET /v1/medication-appointments/received
├── ConfirmDeliveryReceptorTest.php  # PATCH /{id}/confirm-delivery-receptor
└── ConfirmDeliveryDoctorTest.php    # PATCH /{id}/confirm-delivery-doctor
```

### Test Data Setup

- Use existing factories: `User`, `Doctor`, `MedicationOffering`, `MedicationRequest`, `Address`
- Create new factory: `MedicationAppointment`
- Use factory states: `scheduled()`, `completed()`, `receptorConfirmed()`, `doctorConfirmed()`

---

## Test Cases by Endpoint

---

## 1. POST /v1/medication-appointments (StoreTest.php)

### Happy Path Tests

#### T1.1: Receptor creates appointment for confirmed request
```php
it('should allow receptor to create appointment for confirmed request', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $address = Address::factory()->create(['user_id' => $doctor->user->id]);
    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($receptor);

    $response = postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ]);

    $response->assertCreated();

    assertDatabaseHas('medication_appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30:00',
        'status' => 'scheduled',
        'receptor_confirmed' => false,
        'doctor_confirmed' => false,
    ]);
});
```

#### T1.2: Created appointment returns correct JSON structure
```php
it('should return 201 with appointment data including request and address', function (): void {
    // Setup...

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'scheduled_date',
                'scheduled_time',
                'status',
                'receptor_confirmed',
                'doctor_confirmed',
                'created_at',
                'updated_at',
                'medication_request' => [
                    'id',
                    'status',
                    'medication_offering' => ['id', 'drug', 'doctor'],
                ],
                'address' => [
                    'id',
                    'location_name',
                    'full_address',
                ],
            ],
        ])
        ->assertJsonPath('data.status', 'scheduled');
});
```

#### T1.3: Appointment uses doctor's first address automatically
```php
it('should use doctors first address automatically', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor = Doctor::factory()->create();
    $address1 = Address::factory()->create(['user_id' => $doctor->user->id]);
    $address2 = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($receptor);

    $response = postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.address.id', $address1->id);
});
```

#### T1.4: Can schedule for today
```php
it('should allow scheduling for today', function (): void {
    // Setup...

    $response = postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->toDateString(),
        'scheduled_time' => '18:00',
    ]);

    $response->assertCreated();
});
```

### Validation Error Tests

#### T1.5: Missing medication_request_id
```php
it('should return validation error when medication_request_id is missing', function (): void {
    $receptor = User::factory()->receptor()->create();
    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('medication_request_id');
});
```

#### T1.6: Non-existent medication_request_id
```php
it('should return validation error when medication_request_id does not exist', function (): void {
    $receptor = User::factory()->receptor()->create();
    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => 99999,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('medication_request_id');
});
```

#### T1.7: Missing scheduled_date
```php
it('should return validation error when scheduled_date is missing', function (): void {
    // Setup...

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_time' => '14:30',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_date');
});
```

#### T1.8: Past scheduled_date
```php
it('should return validation error when scheduled_date is in the past', function (): void {
    // Setup...

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->subDays(1)->toDateString(),
        'scheduled_time' => '14:30',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_date')
        ->assertJsonFragment(['A data deve ser hoje ou no futuro.']);
});
```

#### T1.9: Invalid date format
```php
it('should return validation error when scheduled_date has invalid format', function (): void {
    // Setup...

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => '15/01/2026', // Wrong format
        'scheduled_time' => '14:30',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_date');
});
```

#### T1.10: Missing scheduled_time
```php
it('should return validation error when scheduled_time is missing', function (): void {
    // Setup...

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_time');
});
```

#### T1.11: Invalid time format
```php
it('should return validation error when scheduled_time has invalid format', function (): void {
    // Setup...

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '2:30 PM', // Wrong format
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_time');
});
```

### Authorization Error Tests

#### T1.12: Unauthenticated user
```php
it('should return 401 unauthorized for unauthenticated users', function (): void {
    $request = MedicationRequest::factory()->confirmed()->create();

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ])->assertUnauthorized();
});
```

#### T1.13: Doctor trying to create appointment
```php
it('should return 403 forbidden when doctor tries to create appointment', function (): void {
    $doctor = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($doctor->user);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ])->assertForbidden();
});
```

#### T1.14: Receptor creating appointment for another receptor's request
```php
it('should return 403 when receptor tries to create appointment for another receptors request', function (): void {
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()
        ->forReceptor($receptor1)
        ->confirmed()
        ->create();

    actingAs($receptor2);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ])->assertForbidden();
});
```

### Business Rule Error Tests

#### T1.15: Pending request (not confirmed)
```php
it('should return 422 when request is not confirmed', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->pending()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ])
        ->assertStatus(422)
        ->assertJson(['message' => 'Apenas solicitações confirmadas podem ter agendamento.']);
});
```

#### T1.16: Rejected request
```php
it('should return 422 when request is rejected', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->rejected()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ])
        ->assertStatus(422)
        ->assertJson(['message' => 'Apenas solicitações confirmadas podem ter agendamento.']);
});
```

#### T1.17: Duplicate appointment
```php
it('should return 409 conflict when appointment already exists for request', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->confirmed()
        ->create();

    // Create existing appointment
    MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ])
        ->assertConflict()
        ->assertJson(['message' => 'Já existe um agendamento para esta solicitação.']);
});
```

#### T1.18: Doctor has no addresses
```php
it('should return 422 when doctor has no registered addresses', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor = Doctor::factory()->create();
    // Don't create any addresses for this doctor
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();

    actingAs($receptor);

    postJson('/api/v1/medication-appointments', [
        'medication_request_id' => $request->id,
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'scheduled_time' => '14:30',
    ])
        ->assertStatus(422)
        ->assertJson(['message' => 'O médico não possui endereços cadastrados.']);
});
```

---

## 2. GET /v1/medication-appointments (ListTest.php)

### Happy Path Tests

#### T2.1: Receptor lists their appointments
```php
it('should return list of receptor appointments', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();
    $appointment = MedicationAppointment::factory()->forRequest($request)->scheduled()->create();

    actingAs($receptor);

    $response = getJson('/api/v1/medication-appointments');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $appointment->id);
});
```

#### T2.2: Returns correct JSON structure
```php
it('should return appointments with correct structure', function (): void {
    // Setup...

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'scheduled_date',
                    'scheduled_time',
                    'status',
                    'receptor_confirmed',
                    'doctor_confirmed',
                    'medication_request',
                    'address',
                ],
            ],
        ]);
});
```

#### T2.3: Returns empty list when no appointments
```php
it('should return empty list when receptor has no appointments', function (): void {
    $receptor = User::factory()->receptor()->create();
    actingAs($receptor);

    getJson('/api/v1/medication-appointments')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
```

#### T2.4: Only returns receptor's own appointments
```php
it('should only return receptors own appointments', function (): void {
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();

    $request1 = MedicationRequest::factory()->forReceptor($receptor1)->confirmed()->create();
    $request2 = MedicationRequest::factory()->forReceptor($receptor2)->confirmed()->create();

    MedicationAppointment::factory()->forRequest($request1)->create();
    MedicationAppointment::factory()->forRequest($request2)->create();

    actingAs($receptor1);

    getJson('/api/v1/medication-appointments')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
```

#### T2.5: Filter by status (scheduled)
```php
it('should filter appointments by status', function (): void {
    $receptor = User::factory()->receptor()->create();

    $request1 = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();
    $request2 = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();

    MedicationAppointment::factory()->forRequest($request1)->scheduled()->create();
    MedicationAppointment::factory()->forRequest($request2)->completed()->create();

    actingAs($receptor);

    getJson('/api/v1/medication-appointments?status=scheduled')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
```

#### T2.6: Sorted by scheduled date (nearest first)
```php
it('should return appointments sorted by scheduled date', function (): void {
    $receptor = User::factory()->receptor()->create();

    $request1 = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();
    $request2 = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();

    $apt1 = MedicationAppointment::factory()->forRequest($request1)->create([
        'scheduled_date' => now()->addDays(5)->toDateString(),
    ]);
    $apt2 = MedicationAppointment::factory()->forRequest($request2)->create([
        'scheduled_date' => now()->addDays(2)->toDateString(),
    ]);

    actingAs($receptor);

    $response = getJson('/api/v1/medication-appointments');

    $response->assertOk()
        ->assertJsonPath('data.0.id', $apt2->id)
        ->assertJsonPath('data.1.id', $apt1->id);
});
```

### Authorization Error Tests

#### T2.7: Unauthenticated user
```php
it('should return 401 for unauthenticated users', function (): void {
    getJson('/api/v1/medication-appointments')
        ->assertUnauthorized();
});
```

#### T2.8: Doctor trying to access receptor endpoint
```php
it('should return 403 when doctor tries to access receptor list', function (): void {
    $doctor = Doctor::factory()->create();
    actingAs($doctor->user);

    getJson('/api/v1/medication-appointments')
        ->assertForbidden();
});
```

---

## 3. GET /v1/medication-appointments/received (ReceivedTest.php)

### Happy Path Tests

#### T3.1: Doctor lists received appointments
```php
it('should return list of doctor received appointments', function (): void {
    $doctor = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);
    $request = MedicationRequest::factory()->forOffering($offering)->confirmed()->create();
    $appointment = MedicationAppointment::factory()->forRequest($request)->create();

    actingAs($doctor->user);

    $response = getJson('/api/v1/medication-appointments/received');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $appointment->id);
});
```

#### T3.2: Returns receptor details
```php
it('should include receptor details in response', function (): void {
    // Setup...

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'medication_request' => [
                        'receptor' => ['id', 'name', 'email', 'phone_number'],
                    ],
                ],
            ],
        ]);
});
```

#### T3.3: Only returns doctor's own offerings' appointments
```php
it('should only return appointments for doctors offerings', function (): void {
    $doctorA = Doctor::factory()->create();
    $doctorB = Doctor::factory()->create();

    $offeringA = MedicationOffering::factory()->create(['doctor_id' => $doctorA->id]);
    $offeringB = MedicationOffering::factory()->create(['doctor_id' => $doctorB->id]);

    $requestA = MedicationRequest::factory()->forOffering($offeringA)->confirmed()->create();
    $requestB = MedicationRequest::factory()->forOffering($offeringB)->confirmed()->create();

    MedicationAppointment::factory()->forRequest($requestA)->create();
    MedicationAppointment::factory()->forRequest($requestB)->create();

    actingAs($doctorA->user);

    getJson('/api/v1/medication-appointments/received')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
```

#### T3.4: Filter by status
```php
it('should filter by status', function (): void {
    // Setup doctor with 2 appointments (scheduled and completed)

    getJson('/api/v1/medication-appointments/received?status=scheduled')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
```

### Authorization Error Tests

#### T3.5: Unauthenticated user
```php
it('should return 401 for unauthenticated users', function (): void {
    getJson('/api/v1/medication-appointments/received')
        ->assertUnauthorized();
});
```

#### T3.6: Receptor trying to access doctor endpoint
```php
it('should return 403 when receptor tries to access doctor list', function (): void {
    $receptor = User::factory()->receptor()->create();
    actingAs($receptor);

    getJson('/api/v1/medication-appointments/received')
        ->assertForbidden();
});
```

---

## 4. PATCH /{id}/confirm-delivery-receptor (ConfirmDeliveryReceptorTest.php)

### Happy Path Tests

#### T4.1: Receptor confirms delivery
```php
it('should allow receptor to confirm delivery', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create(['scheduled_date' => now()->subDays(1)->toDateString()]);

    actingAs($receptor);

    $response = patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor");

    $response->assertOk()
        ->assertJsonPath('data.receptor_confirmed', true)
        ->assertJsonPath('data.status', 'scheduled'); // Still scheduled until both confirm
});
```

#### T4.2: Both confirmations complete appointment
```php
it('should complete appointment when both parties confirm', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create([
            'scheduled_date' => now()->subDays(1)->toDateString(),
            'doctor_confirmed' => true, // Doctor already confirmed
        ]);

    actingAs($receptor);

    $response = patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor");

    $response->assertOk()
        ->assertJsonPath('data.receptor_confirmed', true)
        ->assertJsonPath('data.doctor_confirmed', true)
        ->assertJsonPath('data.status', 'completed');

    // Verify offering is marked completed
    $appointment->refresh();
    expect($appointment->medicationRequest->medicationOffering->status)->toBe('completed');
});
```

#### T4.3: Can confirm on scheduled date
```php
it('should allow confirmation on scheduled date', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create(['scheduled_date' => now()->toDateString()]);

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertOk();
});
```

### Authorization Error Tests

#### T4.4: Unauthenticated user
```php
it('should return 401 for unauthenticated users', function (): void {
    $appointment = MedicationAppointment::factory()->scheduled()->create();

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertUnauthorized();
});
```

#### T4.5: Doctor trying to use receptor endpoint
```php
it('should return 403 when doctor tries to confirm as receptor', function (): void {
    $doctor = Doctor::factory()->create();
    $appointment = MedicationAppointment::factory()->scheduled()->create();

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertForbidden();
});
```

#### T4.6: Wrong receptor
```php
it('should return 403 when receptor confirms another receptors appointment', function (): void {
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()->forReceptor($receptor1)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create(['scheduled_date' => now()->subDays(1)->toDateString()]);

    actingAs($receptor2);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertForbidden();
});
```

### Business Rule Error Tests

#### T4.7: Already completed appointment
```php
it('should return 422 when appointment is already completed', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->completed()
        ->create();

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertStatus(422)
        ->assertJson(['message' => 'Este agendamento já foi concluído.']);
});
```

#### T4.8: Before scheduled date
```php
it('should return 422 when confirming before scheduled date', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create(['scheduled_date' => now()->addDays(5)->toDateString()]);

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertStatus(422)
        ->assertJson(['message' => 'A entrega só pode ser confirmada a partir da data agendada.']);
});
```

#### T4.9: Already confirmed by receptor
```php
it('should return 422 when receptor already confirmed', function (): void {
    $receptor = User::factory()->receptor()->create();
    $request = MedicationRequest::factory()->forReceptor($receptor)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create([
            'scheduled_date' => now()->subDays(1)->toDateString(),
            'receptor_confirmed' => true,
        ]);

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertStatus(422)
        ->assertJson(['message' => 'Você já confirmou a entrega.']);
});
```

### Edge Cases

#### T4.10: Non-existent appointment
```php
it('should return 404 for non-existent appointment', function (): void {
    $receptor = User::factory()->receptor()->create();
    actingAs($receptor);

    patchJson('/api/v1/medication-appointments/99999/confirm-delivery-receptor')
        ->assertNotFound();
});
```

---

## 5. PATCH /{id}/confirm-delivery-doctor (ConfirmDeliveryDoctorTest.php)

### Happy Path Tests

#### T5.1: Doctor confirms delivery
```php
it('should allow doctor to confirm delivery', function (): void {
    $doctor = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request = MedicationRequest::factory()->forOffering($offering)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create(['scheduled_date' => now()->subDays(1)->toDateString()]);

    actingAs($doctor->user);

    $response = patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor");

    $response->assertOk()
        ->assertJsonPath('data.doctor_confirmed', true)
        ->assertJsonPath('data.status', 'scheduled');
});
```

#### T5.2: Both confirmations complete appointment and offering
```php
it('should complete appointment and offering when both confirm', function (): void {
    $doctor = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request = MedicationRequest::factory()->forOffering($offering)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create([
            'scheduled_date' => now()->subDays(1)->toDateString(),
            'receptor_confirmed' => true,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $offering->refresh();
    expect($offering->status)->toBe('completed');
});
```

### Authorization Error Tests

#### T5.3: Unauthenticated user
```php
it('should return 401 for unauthenticated users', function (): void {
    $appointment = MedicationAppointment::factory()->scheduled()->create();

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertUnauthorized();
});
```

#### T5.4: Receptor trying to use doctor endpoint
```php
it('should return 403 when receptor tries to confirm as doctor', function (): void {
    $receptor = User::factory()->receptor()->create();
    $appointment = MedicationAppointment::factory()->scheduled()->create();

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertForbidden();
});
```

#### T5.5: Wrong doctor
```php
it('should return 403 when doctor confirms another doctors appointment', function (): void {
    $doctorA = Doctor::factory()->create();
    $doctorB = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctorA->id]);
    $request = MedicationRequest::factory()->forOffering($offering)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create(['scheduled_date' => now()->subDays(1)->toDateString()]);

    actingAs($doctorB->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertForbidden();
});
```

### Business Rule Error Tests

#### T5.6: Already completed
```php
it('should return 422 when appointment is already completed', function (): void {
    $doctor = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request = MedicationRequest::factory()->forOffering($offering)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->completed()
        ->create();

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertStatus(422)
        ->assertJson(['message' => 'Este agendamento já foi concluído.']);
});
```

#### T5.7: Before scheduled date
```php
it('should return 422 when confirming before scheduled date', function (): void {
    $doctor = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request = MedicationRequest::factory()->forOffering($offering)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create(['scheduled_date' => now()->addDays(5)->toDateString()]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertStatus(422)
        ->assertJson(['message' => 'A entrega só pode ser confirmada a partir da data agendada.']);
});
```

#### T5.8: Already confirmed by doctor
```php
it('should return 422 when doctor already confirmed', function (): void {
    $doctor = Doctor::factory()->create();
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request = MedicationRequest::factory()->forOffering($offering)->confirmed()->create();
    $appointment = MedicationAppointment::factory()
        ->forRequest($request)
        ->scheduled()
        ->create([
            'scheduled_date' => now()->subDays(1)->toDateString(),
            'doctor_confirmed' => true,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertStatus(422)
        ->assertJson(['message' => 'Você já confirmou a entrega.']);
});
```

---

## Test Summary

### Total Test Cases: 48

| Category | Count |
|----------|-------|
| Happy Path | 16 |
| Validation Errors | 7 |
| Authorization Errors | 12 |
| Business Rule Errors | 10 |
| Edge Cases | 3 |

### By Endpoint

| Endpoint | Tests |
|----------|-------|
| POST /v1/medication-appointments | 18 |
| GET /v1/medication-appointments | 8 |
| GET /v1/medication-appointments/received | 6 |
| PATCH /{id}/confirm-delivery-receptor | 10 |
| PATCH /{id}/confirm-delivery-doctor | 8 |

---

## Acceptance Criteria Coverage

| Criteria | Test IDs |
|----------|----------|
| AC1: Appointment Creation | T1.1-T1.18 |
| AC2: Receptor List | T2.1-T2.8 |
| AC3: Doctor List | T3.1-T3.6 |
| AC4: Receptor Confirms | T4.1-T4.10 |
| AC5: Doctor Confirms | T5.1-T5.8 |
| AC6: Dual Confirmation | T4.2, T5.2 |
| AC7: Login Redirect | Manual test |
| AC8: Registration Redirect | Manual test |

---

## Factory Requirements

### New Factory: MedicationAppointmentFactory

```php
<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\MedicationAppointment;
use App\Models\MedicationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicationAppointmentFactory extends Factory
{
    protected $model = MedicationAppointment::class;

    public function definition(): array
    {
        $request = MedicationRequest::factory()->confirmed()->create();
        $doctorUserId = $request->medicationOffering->doctor->user_id;
        $address = Address::factory()->create(['user_id' => $doctorUserId]);

        return [
            'medication_request_id' => $request->id,
            'address_id' => $address->id,
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'scheduled_time' => $this->faker->time('H:i'),
            'status' => 'scheduled',
            'receptor_confirmed' => false,
            'doctor_confirmed' => false,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => 'scheduled']);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'receptor_confirmed' => true,
            'doctor_confirmed' => true,
        ]);
    }

    public function receptorConfirmed(): static
    {
        return $this->state(fn () => ['receptor_confirmed' => true]);
    }

    public function doctorConfirmed(): static
    {
        return $this->state(fn () => ['doctor_confirmed' => true]);
    }

    public function forRequest(MedicationRequest $request): static
    {
        $doctorUserId = $request->medicationOffering->doctor->user_id;
        $address = Address::where('user_id', $doctorUserId)->first()
            ?? Address::factory()->create(['user_id' => $doctorUserId]);

        return $this->state(fn () => [
            'medication_request_id' => $request->id,
            'address_id' => $address->id,
        ]);
    }
}
```

---

## Manual Test Checklist (Bug Fixes)

### Bug 1: Login Redirect

- [ ] Create doctor user in database
- [ ] Create receptor user in database
- [ ] Login as doctor → should redirect to `/(auth)/dashboard`
- [ ] Login as receptor → should redirect to `/(auth)/receptor/search`

### Bug 2: Registration Redirect

- [ ] Register new receptor → should redirect to `/(auth)/receptor/search`
- [ ] Register new doctor → should redirect to `/(auth)/dashboard`

---

## Sign-off

**Phase 3 Status:** ✅ Complete

**Next Phase:** Implementation (Phase 4)

**Test Summary:**
- 48 automated test cases defined
- 5 test files to create
- 1 new factory to create
- 2 manual test scenarios for bug fixes

**Coverage:**
- All 5 endpoints covered
- All acceptance criteria mapped to tests
- Authorization, validation, and business rules tested

**Reviewed By:** Awaiting user confirmation
