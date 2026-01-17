# Phase 3: Test Plan

## Feature: Doctor Appointment Negotiation

**Feature Slug:** `doctor-appointment-negotiation`
**Created:** 2026-01-17
**Status:** Approved

---

## 1. Test Strategy

### Test Types
- **Feature Tests (Integration):** Primary focus - test API endpoints end-to-end
- **Unit Tests:** Only if complex business logic warrants isolation

### Test Framework
- Pest PHP (existing pattern in project)
- Laravel testing helpers (`actingAs`, `patchJson`, etc.)
- Factories for test data setup

### Test Organization
```
tests/Feature/Api/V1/MedicationAppointment/
├── AcceptTest.php           # NEW
├── CounterProposeTest.php   # NEW
├── ConfirmDeliveryDoctorTest.php   # MODIFY (status check update)
├── ConfirmDeliveryReceptorTest.php # MODIFY (status check update)
├── ListTest.php
├── ReceivedTest.php
└── StoreTest.php
```

---

## 2. Test Cases: AcceptTest.php

### 2.1 Happy Path Tests

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| ACC-01 | Doctor accepts receptor's proposal | Status → `confirmed`, returns 200 |
| ACC-02 | Receptor accepts doctor's counter-proposal | Status → `confirmed`, returns 200 |
| ACC-03 | Response includes `proposed_by` field | JSON contains `proposed_by` |
| ACC-04 | Response includes all appointment data | Full resource structure returned |

### 2.2 Authorization Error Tests

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| ACC-05 | Unauthenticated user tries to accept | Returns 401 |
| ACC-06 | Receptor tries to accept own proposal | Returns 403 |
| ACC-07 | Doctor tries to accept own counter-proposal | Returns 403 |
| ACC-08 | Doctor tries to accept another doctor's appointment | Returns 403 |
| ACC-09 | Receptor tries to accept another receptor's appointment | Returns 403 |

### 2.3 Business Rule Error Tests

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| ACC-10 | Try to accept already confirmed appointment | Returns 422 |
| ACC-11 | Try to accept completed appointment | Returns 422 |
| ACC-12 | Try to accept non-existent appointment | Returns 404 |

### 2.4 Test Code Outline

```php
// ACC-01: Doctor accepts receptor's proposal
it('should allow doctor to accept receptor proposal', function (): void {
    // Setup: appointment with proposed_by = 'receptor', status = 'proposed'
    // Action: doctor calls PATCH /accept
    // Assert: status = 'confirmed', returns 200
});

// ACC-06: Receptor tries to accept own proposal
it('should return 403 when receptor tries to accept own proposal', function (): void {
    // Setup: appointment with proposed_by = 'receptor'
    // Action: receptor calls PATCH /accept
    // Assert: returns 403
});
```

---

## 3. Test Cases: CounterProposeTest.php

### 3.1 Happy Path Tests

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| CP-01 | Doctor counter-proposes with new date/time | Appointment updated, `proposed_by` → `doctor`, returns 200 |
| CP-02 | Doctor counter-proposes with new address | Address updated, returns 200 |
| CP-03 | Doctor counter-proposes with all fields | All fields updated correctly |
| CP-04 | Receptor counter-proposes after doctor | `proposed_by` → `receptor`, returns 200 |
| CP-05 | Counter-propose preserves address if not sent | Address remains unchanged |
| CP-06 | Multiple counter-proposals in sequence | Each updates correctly |

### 3.2 Authorization Error Tests

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| CP-07 | Unauthenticated user tries to counter-propose | Returns 401 |
| CP-08 | Receptor tries to counter-propose own proposal | Returns 403 |
| CP-09 | Doctor tries to counter-propose own counter-proposal | Returns 403 |
| CP-10 | Doctor tries to counter-propose another doctor's appointment | Returns 403 |
| CP-11 | Receptor tries to counter-propose another receptor's appointment | Returns 403 |

### 3.3 Validation Error Tests

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| CP-12 | Missing `scheduled_date` | Returns 422 with validation error |
| CP-13 | Missing `scheduled_time` | Returns 422 with validation error |
| CP-14 | Invalid date format | Returns 422 with validation error |
| CP-15 | Invalid time format | Returns 422 with validation error |
| CP-16 | Past date | Returns 422 with validation error |
| CP-17 | Non-existent address_id | Returns 422 with validation error |
| CP-18 | Address belongs to another doctor | Returns 422/403 (business rule) |

### 3.4 Business Rule Error Tests

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| CP-19 | Try to counter-propose confirmed appointment | Returns 422 |
| CP-20 | Try to counter-propose completed appointment | Returns 422 |
| CP-21 | Receptor tries to set address_id | Address ignored (or error) |

### 3.5 Test Code Outline

```php
// CP-01: Doctor counter-proposes with new date/time
it('should allow doctor to counter-propose with new date and time', function (): void {
    // Setup: appointment with proposed_by = 'receptor', status = 'proposed'
    // Action: doctor calls PATCH /counter-propose with new date/time
    // Assert: appointment updated, proposed_by = 'doctor', status = 'proposed'
});

// CP-08: Receptor tries to counter-propose own proposal
it('should return 403 when receptor tries to counter-propose own proposal', function (): void {
    // Setup: appointment with proposed_by = 'receptor'
    // Action: receptor calls PATCH /counter-propose
    // Assert: returns 403
});

// CP-18: Address belongs to another doctor
it('should return 422 when doctor uses another doctors address', function (): void {
    // Setup: appointment, doctor A with address A, doctor B with address B
    // Action: doctor A tries to counter-propose with address B
    // Assert: returns 422
});
```

---

## 4. Test Cases: Existing Tests Updates

### 4.1 ConfirmDeliveryDoctorTest.php

| ID | Test Case | Change Required |
|----|-----------|-----------------|
| CDD-01 | Should reject if appointment not confirmed | Update to check for `confirmed` status |
| CDD-02 | All existing tests | Update factory to use `proposed` status |

### 4.2 ConfirmDeliveryReceptorTest.php

| ID | Test Case | Change Required |
|----|-----------|-----------------|
| CDR-01 | Should reject if appointment not confirmed | Update to check for `confirmed` status |
| CDR-02 | All existing tests | Update factory to use `proposed` → `confirmed` flow |

### 4.3 ListTest.php & ReceivedTest.php

| ID | Test Case | Change Required |
|----|-----------|-----------------|
| LST-01 | Response includes `proposed_by` | Add assertion for new field |

### 4.4 StoreTest.php

| ID | Test Case | Change Required |
|----|-----------|-----------------|
| STO-01 | Creates with `proposed` status | Update assertion from `scheduled` to `proposed` |
| STO-02 | Creates with `proposed_by: receptor` | Add assertion for new field |

---

## 5. Factory Updates

### MedicationAppointmentFactory.php

New methods needed:

```php
/**
 * Indicate that the appointment is proposed (awaiting acceptance).
 */
public function proposed(): static
{
    return $this->state(fn (): array => [
        'status' => 'proposed',
    ]);
}

/**
 * Indicate that the appointment was proposed by the receptor.
 */
public function proposedByReceptor(): static
{
    return $this->state(fn (): array => [
        'proposed_by' => 'receptor',
    ]);
}

/**
 * Indicate that the appointment was proposed by the doctor.
 */
public function proposedByDoctor(): static
{
    return $this->state(fn (): array => [
        'proposed_by' => 'doctor',
    ]);
}

/**
 * Indicate that the appointment is confirmed.
 */
public function confirmed(): static
{
    return $this->state(fn (): array => [
        'status' => 'confirmed',
    ]);
}
```

Update default state:
```php
'status' => 'proposed',
'proposed_by' => 'receptor',
```

---

## 6. Edge Cases & Special Scenarios

### 6.1 Negotiation Flow (E2E)

| ID | Scenario | Test Steps |
|----|----------|------------|
| E2E-01 | Complete negotiation: receptor → doctor accept | 1. Create appointment (receptor proposes) 2. Doctor accepts 3. Verify status = confirmed |
| E2E-02 | One counter-proposal: receptor → doctor counter → receptor accept | 1. Create appointment 2. Doctor counter-proposes 3. Receptor accepts 4. Verify |
| E2E-03 | Multiple counter-proposals | 1. Create 2. Doctor counter 3. Receptor counter 4. Doctor counter 5. Receptor accepts |

### 6.2 Boundary Cases

| ID | Scenario | Expected Result |
|----|----------|-----------------|
| BC-01 | Counter-propose with today's date | Accepted |
| BC-02 | Counter-propose with date far in future (1 year) | Accepted |
| BC-03 | Counter-propose at midnight (00:00) | Accepted |
| BC-04 | Counter-propose at end of day (23:59) | Accepted |

---

## 7. Test Coverage Requirements

### Minimum Coverage
- All new endpoints: 100% happy path + error cases
- All policy methods: authorization tests
- All validation rules: validation error tests
- Updated existing tests: pass with new status flow

### Quality Gates
- [ ] All tests pass: `php artisan test`
- [ ] Static analysis clean: `composer phpstan`
- [ ] Code style clean: `composer pint`

---

## 8. Test Data Setup Patterns

### Standard Setup for Accept Tests

```php
// Doctor needs to respond (receptor proposed)
$doctor = Doctor::factory()->create();
$receptor = User::factory()->receptor()->create();
$address = Address::factory()->create(['user_id' => $doctor->user->id]);
$offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
$request = MedicationRequest::factory()
    ->forReceptor($receptor)
    ->forOffering($offering)
    ->confirmed()
    ->create();
$appointment = MedicationAppointment::factory()
    ->proposed()
    ->proposedByReceptor()
    ->create([
        'medication_request_id' => $request->id,
        'address_id' => $address->id,
    ]);
```

### Standard Setup for Counter-Propose Tests

```php
// Same as above, but test both directions:
// 1. Doctor counter-proposes (proposed_by = receptor)
// 2. Receptor counter-proposes (proposed_by = doctor)
```

---

## 9. Files to Create/Modify

### New Test Files

| File | Tests |
|------|-------|
| `tests/Feature/Api/V1/MedicationAppointment/AcceptTest.php` | 12 tests |
| `tests/Feature/Api/V1/MedicationAppointment/CounterProposeTest.php` | 21 tests |

### Modified Test Files

| File | Changes |
|------|---------|
| `tests/Feature/Api/V1/MedicationAppointment/ConfirmDeliveryDoctorTest.php` | Update status checks |
| `tests/Feature/Api/V1/MedicationAppointment/ConfirmDeliveryReceptorTest.php` | Update status checks |
| `tests/Feature/Api/V1/MedicationAppointment/ListTest.php` | Add `proposed_by` assertions |
| `tests/Feature/Api/V1/MedicationAppointment/ReceivedTest.php` | Add `proposed_by` assertions |
| `tests/Feature/Api/V1/MedicationAppointment/StoreTest.php` | Update status/proposed_by assertions |

### Modified Factory

| File | Changes |
|------|---------|
| `database/factories/MedicationAppointmentFactory.php` | Add new states, update defaults |

---

## 10. Acceptance Criteria Traceability

| Acceptance Criteria | Test Cases |
|---------------------|------------|
| AC-01: Doctor can view pending appointments | ReceivedTest (existing) |
| AC-02: Doctor can accept receptor's proposal | ACC-01 |
| AC-03: Doctor can counter-propose | CP-01, CP-02, CP-03 |
| AC-04: Receptor can view appointments awaiting response | ListTest (existing) |
| AC-05: Receptor can accept doctor's counter-proposal | ACC-02 |
| AC-06: Receptor can counter-propose again | CP-04 |
| AC-07: Appointment shows `confirmed` after acceptance | ACC-01, ACC-02 |
| AC-08: Existing delivery confirmation works after `confirmed` | CDD-01, CDR-01 (updated) |
| AC-09: Existing data migrated correctly | Manual verification after migration |
