# Phase 2: Solution Design

## Feature: Doctor Appointment Negotiation

**Feature Slug:** `doctor-appointment-negotiation`
**Created:** 2026-01-17
**Status:** Approved

---

## 1. Architecture Overview

### Data Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           NEGOTIATION FLOW                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  [Receptor]                                [Doctor]                         │
│      │                                         │                            │
│      │  1. Create appointment proposal         │                            │
│      │  POST /medication-appointments          │                            │
│      │  (date, time) ───────────────────────▶  │                            │
│      │                                         │                            │
│      │  2a. Accept proposal                    │                            │
│      │  PATCH /.../{id}/accept ◀───────────── │                            │
│      │  status → confirmed                     │                            │
│      │                                         │                            │
│      │  2b. Counter-propose                    │                            │
│      │  PATCH /.../{id}/counter-propose ◀──── │                            │
│      │  (new date, time, address)              │                            │
│      │                                         │                            │
│      │  3a. Accept counter-proposal            │                            │
│      │  PATCH /.../{id}/accept ───────────────▶│                            │
│      │  status → confirmed                     │                            │
│      │                                         │                            │
│      │  3b. Counter-propose again              │                            │
│      │  PATCH /.../{id}/counter-propose ──────▶│                            │
│      │                                         │                            │
│      │  ... (cycle until acceptance)           │                            │
│      │                                         │                            │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Database Changes

### 2.1 Migration: Add `proposed_by` Column

**File:** `web/database/migrations/2026_01_17_000001_add_proposed_by_to_medication_appointments_table.php`

```php
Schema::table('medication_appointments', function (Blueprint $table): void {
    $table->string('proposed_by', 10)->default('receptor')->after('status');
});
```

### 2.2 Migration: Update Status Values

**File:** `web/database/migrations/2026_01_17_000002_update_medication_appointment_status_values.php`

```php
// Convert existing 'scheduled' status to 'proposed'
DB::table('medication_appointments')
    ->where('status', 'scheduled')
    ->update(['status' => 'proposed']);
```

### 2.3 Final Schema

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `medication_request_id` | bigint | FK to medication_requests (unique) |
| `address_id` | bigint | FK to addresses |
| `scheduled_date` | date | Proposed date |
| `scheduled_time` | time | Proposed time |
| `status` | string(20) | `proposed`, `confirmed`, `completed` |
| `proposed_by` | string(10) | `receptor` or `doctor` |
| `receptor_confirmed` | boolean | Delivery confirmation by receptor |
| `doctor_confirmed` | boolean | Delivery confirmation by doctor |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

---

## 3. Backend Components

### 3.1 Model Changes

**File:** `web/app/Models/MedicationAppointment.php`

Changes:
- Add `proposed_by` to `$fillable`
- Add `scopeProposed()` for filtering proposed appointments
- Add `scopeConfirmed()` for filtering confirmed appointments
- Remove `scopeScheduled()` (deprecated status)
- Add helper method `needsResponseFrom(User $user): bool`

### 3.2 New Actions

| Action | Purpose |
|--------|---------|
| `AcceptAppointmentAction` | Accept a proposed appointment → set status to `confirmed` |
| `CounterProposeAppointmentAction` | Update date/time/address and flip `proposed_by` |

**File:** `web/app/Actions/MedicationAppointment/AcceptAppointmentAction.php`

```php
class AcceptAppointmentAction
{
    public function execute(MedicationAppointment $appointment): MedicationAppointment
    {
        $appointment->update(['status' => 'confirmed']);

        return $appointment->fresh([...relations...]);
    }
}
```

**File:** `web/app/Actions/MedicationAppointment/CounterProposeAppointmentAction.php`

```php
class CounterProposeAppointmentAction
{
    public function execute(
        MedicationAppointment $appointment,
        string $scheduledDate,
        string $scheduledTime,
        ?int $addressId,
        string $proposedBy
    ): MedicationAppointment {
        $updateData = [
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'proposed_by' => $proposedBy,
        ];

        if ($addressId !== null) {
            $updateData['address_id'] = $addressId;
        }

        $appointment->update($updateData);

        return $appointment->fresh([...relations...]);
    }
}
```

### 3.3 New Controllers

| Controller | Route | Purpose |
|------------|-------|---------|
| `AcceptController` | `PATCH /{id}/accept` | Accept appointment proposal |
| `CounterProposeController` | `PATCH /{id}/counter-propose` | Counter-propose new date/time/address |

**File:** `web/app/Http/Controllers/Api/V1/MedicationAppointment/AcceptController.php`

Responsibilities:
- Authorize via policy (`accept` method)
- Validate appointment is in `proposed` status
- Validate user is the one who needs to respond (not the proposer)
- Delegate to `AcceptAppointmentAction`
- Return `MedicationAppointmentResource`

**File:** `web/app/Http/Controllers/Api/V1/MedicationAppointment/CounterProposeController.php`

Responsibilities:
- Authorize via policy (`counterPropose` method)
- Validate via `CounterProposeAppointmentRequest`
- Validate appointment is in `proposed` status
- Validate user is the one who needs to respond
- If doctor: validate address belongs to them
- Delegate to `CounterProposeAppointmentAction`
- Return `MedicationAppointmentResource`

### 3.4 New Form Request

**File:** `web/app/Http/Requests/Api/V1/MedicationAppointment/CounterProposeAppointmentRequest.php`

```php
public function rules(): array
{
    return [
        'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
        'scheduled_time' => ['required', 'date_format:H:i'],
        'address_id' => ['sometimes', 'nullable', 'exists:addresses,id'],
    ];
}

public function messages(): array
{
    return [
        'scheduled_date.required' => 'A data é obrigatória.',
        'scheduled_date.after_or_equal' => 'A data deve ser hoje ou no futuro.',
        'scheduled_time.required' => 'O horário é obrigatório.',
        'scheduled_time.date_format' => 'O horário deve estar no formato HH:MM.',
        'address_id.exists' => 'O endereço selecionado não existe.',
    ];
}
```

### 3.5 Policy Updates

**File:** `web/app/Policies/MedicationAppointmentPolicy.php`

New methods:

```php
/**
 * Determine if the user can accept the appointment.
 * Only the party who did NOT propose can accept.
 */
public function accept(User $user, MedicationAppointment $appointment): bool
{
    return $this->canRespond($user, $appointment);
}

/**
 * Determine if the user can counter-propose.
 * Only the party who did NOT propose can counter-propose.
 */
public function counterPropose(User $user, MedicationAppointment $appointment): bool
{
    return $this->canRespond($user, $appointment);
}

/**
 * Check if user is the one who needs to respond (not the proposer).
 */
private function canRespond(User $user, MedicationAppointment $appointment): bool
{
    $isReceptor = $user->role === 'receptor'
        && $appointment->medicationRequest->receptor_id === $user->id;

    $isDoctor = $user->role === 'doctor'
        && $user->doctor !== null
        && $appointment->medicationRequest->medicationOffering->doctor_id === $user->doctor->id;

    if ($appointment->proposed_by === 'receptor') {
        return $isDoctor; // Doctor needs to respond
    }

    return $isReceptor; // Receptor needs to respond
}
```

### 3.6 Resource Updates

**File:** `web/app/Http/Resources/Api/V1/MedicationAppointmentResource.php`

Add `proposed_by` to the response:

```php
return [
    'id' => $this->id,
    'scheduled_date' => $this->scheduled_date->toDateString(),
    'scheduled_time' => $this->scheduled_time,
    'status' => $this->status,
    'proposed_by' => $this->proposed_by,  // NEW
    // ... rest
];
```

### 3.7 Route Registration

**File:** `web/routes/api.php`

Add new routes:

```php
Route::patch(
    '/{medicationAppointment}/accept',
    MedicationAppointment\AcceptController::class
)->name('api.v1.medication-appointments.accept');

Route::patch(
    '/{medicationAppointment}/counter-propose',
    MedicationAppointment\CounterProposeController::class
)->name('api.v1.medication-appointments.counter-propose');
```

### 3.8 Existing Code Updates

#### StoreController

**File:** `web/app/Http/Controllers/Api/V1/MedicationAppointment/StoreController.php`

No changes needed - already creates with default status. The migration will change default from `scheduled` to `proposed`.

#### CreateMedicationAppointmentAction

**File:** `web/app/Actions/MedicationAppointment/CreateMedicationAppointmentAction.php`

Update to explicitly set `proposed_by`:

```php
$appointment = MedicationAppointment::create([
    'medication_request_id' => $medicationRequest->id,
    'address_id' => $address->id,
    'scheduled_date' => $scheduledDate,
    'scheduled_time' => $scheduledTime,
    'status' => 'proposed',
    'proposed_by' => 'receptor',
]);
```

#### ConfirmDeliveryDoctorController & ConfirmDeliveryReceptorController

Update status check from `'scheduled'` to `['proposed', 'confirmed']` - delivery can only be confirmed after appointment is confirmed:

```php
// Only confirmed appointments can have delivery confirmed
if ($medicationAppointment->status !== 'confirmed') {
    return response()->json([
        'message' => 'O agendamento precisa ser confirmado antes da entrega.',
    ], 422);
}
```

---

## 4. Files to Create

| # | File Path | Type |
|---|-----------|------|
| 1 | `web/database/migrations/2026_01_17_000001_add_proposed_by_to_medication_appointments_table.php` | Migration |
| 2 | `web/database/migrations/2026_01_17_000002_update_medication_appointment_status_values.php` | Migration |
| 3 | `web/app/Actions/MedicationAppointment/AcceptAppointmentAction.php` | Action |
| 4 | `web/app/Actions/MedicationAppointment/CounterProposeAppointmentAction.php` | Action |
| 5 | `web/app/Http/Controllers/Api/V1/MedicationAppointment/AcceptController.php` | Controller |
| 6 | `web/app/Http/Controllers/Api/V1/MedicationAppointment/CounterProposeController.php` | Controller |
| 7 | `web/app/Http/Requests/Api/V1/MedicationAppointment/CounterProposeAppointmentRequest.php` | Request |

---

## 5. Files to Modify

| # | File Path | Changes |
|---|-----------|---------|
| 1 | `web/app/Models/MedicationAppointment.php` | Add `proposed_by` to fillable, update scopes |
| 2 | `web/app/Policies/MedicationAppointmentPolicy.php` | Add `accept`, `counterPropose` methods |
| 3 | `web/app/Http/Resources/Api/V1/MedicationAppointmentResource.php` | Add `proposed_by` field |
| 4 | `web/routes/api.php` | Add new routes |
| 5 | `web/app/Actions/MedicationAppointment/CreateMedicationAppointmentAction.php` | Set `proposed_by` explicitly |
| 6 | `web/app/Http/Controllers/Api/V1/MedicationAppointment/ConfirmDeliveryDoctorController.php` | Update status check |
| 7 | `web/app/Http/Controllers/Api/V1/MedicationAppointment/ConfirmDeliveryReceptorController.php` | Update status check |

---

## 6. Implementation Order

```
1. Migrations (schema changes)
   ├── Add proposed_by column
   └── Update existing status values

2. Model updates
   └── MedicationAppointment (fillable, scopes)

3. Actions (business logic)
   ├── AcceptAppointmentAction
   └── CounterProposeAppointmentAction

4. Policy updates
   └── MedicationAppointmentPolicy (accept, counterPropose)

5. Form Request
   └── CounterProposeAppointmentRequest

6. Controllers
   ├── AcceptController
   └── CounterProposeController

7. Resource updates
   └── MedicationAppointmentResource

8. Route registration
   └── api.php

9. Existing controller updates
   ├── ConfirmDeliveryDoctorController
   ├── ConfirmDeliveryReceptorController
   └── CreateMedicationAppointmentAction
```

---

## 7. API Contract

### Accept Appointment

```
PATCH /api/v1/medication-appointments/{id}/accept

Authorization: Bearer <token>

Request Body: (empty)

Response 200:
{
    "data": {
        "id": 1,
        "scheduled_date": "2026-01-20",
        "scheduled_time": "14:00",
        "status": "confirmed",
        "proposed_by": "receptor",
        "receptor_confirmed": false,
        "doctor_confirmed": false,
        ...
    }
}

Response 403: Unauthorized (not the responder)
Response 422: Invalid status (not proposed)
```

### Counter-Propose Appointment

```
PATCH /api/v1/medication-appointments/{id}/counter-propose

Authorization: Bearer <token>

Request Body:
{
    "scheduled_date": "2026-01-21",
    "scheduled_time": "10:00",
    "address_id": 2  // Optional, only for doctors
}

Response 200:
{
    "data": {
        "id": 1,
        "scheduled_date": "2026-01-21",
        "scheduled_time": "10:00",
        "status": "proposed",
        "proposed_by": "doctor",
        "receptor_confirmed": false,
        "doctor_confirmed": false,
        ...
    }
}

Response 403: Unauthorized
Response 422: Validation error or invalid status
```

---

## 8. Technical Decisions

| Decision | Rationale |
|----------|-----------|
| Single migration for `proposed_by` | Simpler than combined migration |
| Separate migration for status update | Data migration should be separate from schema |
| `proposed_by` as string not enum | Laravel doesn't have native enum support, string is simpler |
| Default `proposed_by` to `receptor` | Maintains backward compatibility |
| No transaction in accept/counter-propose | Single update, no complex state changes |
| Address validation in controller | Business rule, not data validation |

---

## 9. Edge Cases Handled

| Case | Handling |
|------|----------|
| User tries to accept own proposal | Policy denies (canRespond check) |
| User tries to counter-propose when it's not their turn | Policy denies |
| Doctor tries to use another doctor's address | Controller validates ownership |
| Receptor tries to change address | Request doesn't allow (address_id ignored) |
| Accept/counter-propose on confirmed appointment | Controller returns 422 |
| Accept/counter-propose on completed appointment | Controller returns 422 |

---

## 10. Out of Scope (Confirmed)

- Mobile app changes (separate feature)
- Notifications
- Appointment cancellation
- History of proposals
