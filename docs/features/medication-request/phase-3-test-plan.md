# Phase 3: Test Plan - Medication Request System

## Overview

This document defines all test cases for the Medication Request feature, following TDD principles. Tests will be implemented using Pest PHP framework, following existing project patterns.

---

## Test Strategy

### Test Types

| Type | Coverage | Location |
|------|----------|----------|
| Feature Tests | API endpoints, full request/response cycle | `tests/Feature/Api/V1/MedicationRequest/` |
| Unit Tests | Actions, isolated business logic | `tests/Unit/Actions/MedicationRequest/` (if needed) |

### Test Conventions

- Use Pest PHP syntax (`it()`, `test()`, `expect()`)
- Use factories for test data
- Use `actingAs()` for authentication
- Use Laravel testing helpers (`postJson`, `getJson`, `patchJson`)
- Assert HTTP status codes, JSON structure, and database state
- Test descriptions should be clear and descriptive

---

## Test Files Structure

```
tests/Feature/Api/V1/MedicationRequest/
├── StoreTest.php      # POST /medication-requests
├── ListTest.php       # GET /medication-requests
├── ReceivedTest.php   # GET /medication-requests/received
├── ConfirmTest.php    # PATCH /medication-requests/{id}/confirm
└── RejectTest.php     # PATCH /medication-requests/{id}/reject
```

---

## Test Cases by Endpoint

### 1. StoreTest.php - POST /api/v1/medication-requests

#### Happy Path

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| S01 | Receptor creates request for available offering | 201 Created, request created with status 'pending' |
| S02 | Request is associated with authenticated receptor | Database has correct receptor_id |
| S03 | Offering status changes to 'reserved' after request | Offering status updated in database |
| S04 | Response includes request data with offering details | JSON contains id, status, medication_offering |

#### Validation Errors

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| S05 | Missing medication_offering_id | 422 with validation error |
| S06 | Invalid medication_offering_id (non-existent) | 422 with "A oferta de medicamento não existe." |
| S07 | medication_offering_id is not an integer | 422 with validation error |

#### Authorization Errors

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| S08 | Unauthenticated user tries to create request | 401 Unauthorized |
| S09 | Doctor tries to create request | 403 Forbidden |
| S10 | User with no role tries to create request | 403 Forbidden |

#### Business Rule Errors

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| S11 | Receptor tries to request already reserved offering | 409 Conflict with "Esta oferta já foi reservada" |
| S12 | Receptor tries to request same offering twice | 409 Conflict (duplicate) |

#### Edge Cases

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| S13 | Request offering that was previously rejected | 201 Created (new request allowed) |
| S14 | Content-Type header is application/json | Response header check |

---

### 2. ListTest.php - GET /api/v1/medication-requests

#### Happy Path

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| L01 | Receptor lists their own requests | 200 OK with array of requests |
| L02 | Returns empty array when receptor has no requests | 200 OK with empty data array |
| L03 | Response includes offering and drug details | JSON structure includes nested relations |
| L04 | Requests ordered by created_at descending | Most recent first |
| L05 | Only returns authenticated receptor's requests | No other receptor's data |

#### Authorization Errors

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| L06 | Unauthenticated user tries to list | 401 Unauthorized |
| L07 | Doctor tries to list (should use /received) | 200 OK with empty array (no requests as receptor) |

#### Data Isolation

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| L08 | Receptor A cannot see Receptor B's requests | Only own requests returned |

---

### 3. ReceivedTest.php - GET /api/v1/medication-requests/received

#### Happy Path

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| R01 | Doctor lists requests for their offerings | 200 OK with array of requests |
| R02 | Returns empty array when no requests received | 200 OK with empty data array |
| R03 | Response includes receptor details | JSON contains receptor name, email, phone |
| R04 | Response includes offering and drug details | JSON structure with nested relations |
| R05 | Requests ordered by created_at descending | Most recent first |

#### Filtering

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| R06 | Filter by status=pending | Only pending requests returned |
| R07 | Filter by status=confirmed | Only confirmed requests returned |
| R08 | Filter by status=rejected | Only rejected requests returned |
| R09 | No filter returns all statuses | All requests returned |

#### Authorization Errors

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| R10 | Unauthenticated user tries to access | 401 Unauthorized |
| R11 | Receptor tries to access | 403 Forbidden (or empty - doctors only) |

#### Data Isolation

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| R12 | Doctor A cannot see Doctor B's received requests | Only own offerings' requests |

---

### 4. ConfirmTest.php - PATCH /api/v1/medication-requests/{id}/confirm

#### Happy Path

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| C01 | Doctor confirms pending request for their offering | 200 OK, status changed to 'confirmed' |
| C02 | Offering remains reserved after confirmation | Offering status still 'reserved' |
| C03 | Response includes updated request data | JSON with status='confirmed' |

#### Authorization Errors

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| C04 | Unauthenticated user tries to confirm | 401 Unauthorized |
| C05 | Receptor tries to confirm request | 403 Forbidden |
| C06 | Doctor tries to confirm another doctor's request | 403 Forbidden |

#### Business Rule Errors

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| C07 | Doctor tries to confirm already confirmed request | 422 "Esta solicitação já foi processada" |
| C08 | Doctor tries to confirm rejected request | 422 "Esta solicitação já foi processada" |

#### Edge Cases

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| C09 | Request not found (invalid ID) | 404 Not Found |

---

### 5. RejectTest.php - PATCH /api/v1/medication-requests/{id}/reject

#### Happy Path

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| J01 | Doctor rejects pending request for their offering | 200 OK, status changed to 'rejected' |
| J02 | Offering status returns to 'available' after rejection | Offering status updated |
| J03 | Response includes updated request data | JSON with status='rejected' |
| J04 | Another receptor can request after rejection | New request succeeds |

#### Authorization Errors

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| J05 | Unauthenticated user tries to reject | 401 Unauthorized |
| J06 | Receptor tries to reject request | 403 Forbidden |
| J07 | Doctor tries to reject another doctor's request | 403 Forbidden |

#### Business Rule Errors

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| J08 | Doctor tries to reject already confirmed request | 422 "Esta solicitação já foi processada" |
| J09 | Doctor tries to reject already rejected request | 422 "Esta solicitação já foi processada" |

#### Edge Cases

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| J10 | Request not found (invalid ID) | 404 Not Found |

---

## Model & Relationship Tests

### MedicationRequest Model

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| M01 | Request belongs to receptor (User) | Relationship works |
| M02 | Request belongs to MedicationOffering | Relationship works |
| M03 | Scope pending filters correctly | Only pending returned |
| M04 | Scope confirmed filters correctly | Only confirmed returned |
| M05 | Scope rejected filters correctly | Only rejected returned |

### MedicationOffering Model Updates

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| M06 | Offering has many requests | Relationship works |
| M07 | Offering has one active request (pending) | Relationship returns single |
| M08 | Scope available filters by status | Only available returned |
| M09 | Scope reserved filters by status | Only reserved returned |
| M10 | Default status is 'available' | New offerings have correct default |

---

## Database Constraint Tests

| ID | Test Case | Expected Result |
|----|-----------|-----------------|
| D01 | Cannot create two pending requests for same offering | Database constraint violation |
| D02 | Can create pending request after previous was rejected | Constraint allows |
| D03 | Cascade delete: offering deleted removes requests | Requests deleted |
| D04 | Cascade delete: receptor deleted removes requests | Requests deleted |

---

## Integration Scenarios

### Complete Flow Tests

| ID | Test Case | Steps | Expected Result |
|----|-----------|-------|-----------------|
| I01 | Full request-confirm flow | 1. Receptor requests, 2. Doctor confirms | Both succeed, status correct |
| I02 | Full request-reject-rerequest flow | 1. Request, 2. Reject, 3. New request | All succeed, offering available again |
| I03 | Concurrent request attempt | Two receptors request same offering | One succeeds, one fails with 409 |

---

## Test Data Requirements

### Factories Needed

```php
// MedicationRequestFactory
MedicationRequest::factory()
    ->pending()      // status = 'pending'
    ->confirmed()    // status = 'confirmed'
    ->rejected()     // status = 'rejected'
    ->forOffering($offering)
    ->forReceptor($user)
```

### Factory States

| State | Description |
|-------|-------------|
| `pending` | Request with status 'pending' |
| `confirmed` | Request with status 'confirmed' |
| `rejected` | Request with status 'rejected' |

### Existing Factories to Use

- `User::factory()->receptor()` - User with role='receptor'
- `User::factory()->doctor()` - User with Doctor relationship
- `Doctor::factory()` - Creates Doctor with User
- `MedicationOffering::factory()` - Creates offering
- `Drug::factory()` - Creates drug

---

## Acceptance Criteria Validation

| Criteria | Test IDs |
|----------|----------|
| AC1: Receptor can request available offering | S01, S02, S03 |
| AC1: Cannot request reserved offering | S11 |
| AC2: Receptor sees their requests | L01, L03, L05 |
| AC3: Doctor sees received requests | R01, R03, R04 |
| AC4: Doctor confirms pending request | C01, C02 |
| AC4: Only owner can confirm | C06 |
| AC5: Doctor rejects pending request | J01, J02 |
| AC5: Offering returns to available | J02, J04 |

---

## Test Execution Plan

### Order of Implementation

1. **Factory first:** Create MedicationRequestFactory
2. **Model tests:** Verify relationships work
3. **StoreTest:** Create request endpoint
4. **ListTest:** Receptor's list endpoint
5. **ReceivedTest:** Doctor's received endpoint
6. **ConfirmTest:** Confirm endpoint
7. **RejectTest:** Reject endpoint
8. **Integration tests:** Full flow scenarios

### Running Tests

```bash
# Run all medication request tests
./vendor/bin/pest tests/Feature/Api/V1/MedicationRequest/

# Run specific test file
./vendor/bin/pest tests/Feature/Api/V1/MedicationRequest/StoreTest.php

# Run with coverage
./vendor/bin/pest --coverage tests/Feature/Api/V1/MedicationRequest/
```

---

## Coverage Requirements

- **Minimum:** All happy path tests (S01-S04, L01-L05, R01-R05, C01-C03, J01-J04)
- **Required:** All authorization tests (401, 403 scenarios)
- **Required:** All business rule validations (409, 422 scenarios)
- **Recommended:** Edge cases and integration tests

---

## Phase 3 Sign-off

**Status:** Complete
**Total Test Cases:** 54 test cases identified
**Next Phase:** Implementation (Phase 4)
**Estimated Test Files:** 5 feature test files

---

## Quick Reference: Test Case Summary

| Endpoint | Happy Path | Auth Errors | Validation | Business Rules | Edge Cases | Total |
|----------|------------|-------------|------------|----------------|------------|-------|
| POST /requests | 4 | 3 | 3 | 2 | 2 | 14 |
| GET /requests | 5 | 2 | 0 | 0 | 1 | 8 |
| GET /received | 5 | 2 | 0 | 0 | 5 | 12 |
| PATCH /confirm | 3 | 3 | 0 | 2 | 1 | 9 |
| PATCH /reject | 4 | 3 | 0 | 2 | 1 | 10 |
| **Total** | **21** | **13** | **3** | **6** | **10** | **53** |

Plus 10 model/relationship tests and 4 database constraint tests = **67 total test scenarios**
