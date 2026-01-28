# Project Context

## Overview

**DoaFarma** is a medication donation platform that connects doctors who have excess medications with patients (receptors) who need them. This is an academic project (TCC - undergraduate thesis).

The platform facilitates the complete donation flow: from medication offering to request, appointment scheduling, delivery confirmation, and doctor evaluation.

---

## Domain Entities

### User

Base entity for authentication. Has a `role` that defines the user type and `status` for registration approval workflow.

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| name | string | Full name |
| email | string | Unique, used for login |
| cpf | string | Brazilian tax ID (receptors only, encrypted) |
| cpf_hash | string | Hash for uniqueness check |
| phone_number | string | 10-11 digits, unique |
| password | string | Hashed password |
| role | enum | `doctor`, `receptor`, or `admin` |
| status | enum | `pending`, `approved`, or `rejected` |
| terms_accepted | boolean | Must accept terms to register |
| status_changed_at | timestamp | When status was last changed |
| status_changed_by | integer | Admin who changed status |

**Relationships:**
- `hasOne` Doctor (if role is doctor)
- `hasMany` Address
- `hasMany` PushToken

---

### Doctor

Medical professional who donates medications. Extends User with CRM credentials.

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| user_id | integer | Foreign key to users |
| crm | string | Medical license number (6 digits) |
| crm_uf | string | State code (2 letters, e.g., SP, RJ) |

**Relationships:**
- `belongsTo` User
- `hasMany` MedicationOffering
- `hasMany` DoctorRating (received ratings)

**Validations:**
- CRM: exactly 6 digits
- CRM_UF: valid Brazilian state (AC, AL, AP, AM, BA, CE, DF, ES, GO, MA, MT, MS, MG, PA, PB, PR, PE, PI, RJ, RN, RS, RO, RR, SC, SP, SE, TO)
- CRM + CRM_UF combination must be unique

---

### Drug

Pharmaceutical products registered in ANVISA (Brazilian FDA). Read-only catalog.

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| product_name | string | Commercial name |
| substance | string | Active ingredient |
| laboratory | string | Manufacturer |
| registration_number | string | ANVISA registration |
| presentation | string | Dosage form and quantity |
| stripe_color | enum | Regulatory classification (`yellow`, `red`, `black`) |

**Search:**
- Full-text search index on `product_name`, `substance`, `laboratory`
- Portuguese language stemming enabled

---

### MedicationOffering

A doctor's offer to donate specific medications.

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| doctor_id | integer | Foreign key to doctors |
| drug_id | integer | Foreign key to drugs |
| lot_number | string | Batch identifier (max 50 chars) |
| expires_at | date | Expiration date |
| quantity | integer | Units available (1-100,000) |
| status | enum | `available`, `reserved`, `completed` |

**Relationships:**
- `belongsTo` Doctor
- `belongsTo` Drug
- `hasOne` MedicationRequest

**Status Transitions:**
```
available → reserved (when request created)
reserved → available (when request rejected)
reserved → completed (when delivery confirmed)
```

**Validations:**
- Expiration: must be future date, max 10 years ahead
- Quantity: 1 to 100,000

---

### MedicationRequest

A receptor's request for a medication offering.

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| receptor_id | integer | Foreign key to users (receptor) |
| medication_offering_id | integer | Foreign key to medication_offerings |
| status | enum | `pending`, `confirmed`, `rejected` |

**Relationships:**
- `belongsTo` User (receptor)
- `belongsTo` MedicationOffering
- `hasOne` MedicationAppointment

**Status Transitions:**
```
pending → confirmed (doctor confirms)
pending → rejected (doctor rejects)
```

**Business Rules:**
- Only one pending/confirmed request per offering (partial unique index)
- Receptor cannot request their own offering
- Offering must be `available` to create request

---

### MedicationAppointment

Scheduled meeting for medication handoff.

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| medication_request_id | integer | Foreign key to medication_requests |
| address_id | integer | Foreign key to addresses (doctor's address) |
| scheduled_date | date | Appointment date |
| scheduled_time | time | Appointment time |
| status | enum | `proposed`, `confirmed`, `completed` |
| proposed_by | enum | `doctor` or `receptor` |
| doctor_confirmed | boolean | Doctor confirmed delivery |
| receptor_confirmed | boolean | Receptor confirmed receipt |

**Relationships:**
- `belongsTo` MedicationRequest
- `belongsTo` Address

**Status Transitions:**
```
proposed → confirmed (both parties accept time)
proposed → proposed (counter-proposal resets confirmations)
confirmed → completed (both confirm delivery)
```

**Business Rules:**
- Either party can propose initial appointment
- Counter-proposal resets the other party's confirmation
- Both must confirm delivery for completion

---

### DoctorRating

Receptor's evaluation of doctor after completed donation.

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| medication_appointment_id | integer | Foreign key to appointments |
| doctor_id | integer | Foreign key to doctors |
| receptor_id | integer | Foreign key to users |
| rating | integer | 1-5 stars |
| comment | string | Optional feedback (max 1000 chars) |

**Relationships:**
- `belongsTo` MedicationAppointment
- `belongsTo` Doctor
- `belongsTo` User (receptor)

**Business Rules:**
- Only the receptor who participated can rate
- Only one rating per appointment
- Rating cannot be deleted (only comment can be updated)

---

### Address

Physical location associated with a user (primarily doctors for pickup).

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| user_id | integer | Foreign key to users |
| location_name | string | Identifier (e.g., "Consultório") |
| full_address | string | Complete address |
| complement | string | Optional additional info |
| cep | string | Brazilian postal code (8 digits) |

**Relationships:**
- `belongsTo` User

**Validations:**
- CEP: exactly 8 digits
- Doctors must have at least one address

---

### PushToken

Device tokens for push notifications.

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| user_id | integer | Foreign key to users |
| token | string | Expo push token |
| device_type | enum | `ios` or `android` |

**Relationships:**
- `belongsTo` User

**Lifecycle:**
- Created on app login/startup
- Deleted on logout
- Updated if token changes

---

## Entity Relationships Diagram

```
┌──────────┐         ┌────────────┐         ┌──────────┐
│   User   │◄────────│   Doctor   │────────►│ Address  │
│          │ 1     1 │            │ 1     * │          │
│ role     │         │ crm        │         │ cep      │
│ status   │         │ crm_uf     │         │          │
└──────────┘         └────────────┘         └──────────┘
     │                     │
     │ 1                   │ 1
     ▼ *                   ▼ *
┌──────────┐         ┌──────────────────┐         ┌──────────┐
│ PushToken│         │MedicationOffering│◄────────│   Drug   │
│          │         │                  │ *     1 │          │
│ token    │         │ status           │         │ substance│
│ device   │         │ quantity         │         │          │
└──────────┘         └──────────────────┘         └──────────┘
                           │
                           │ 1
                           ▼ 1
                     ┌──────────────────┐
     ┌───────────────│MedicationRequest │
     │               │                  │
     │ receptor_id   │ status           │
     │               └──────────────────┘
     │                     │
     │                     │ 1
     ▼                     ▼ 1
┌──────────┐         ┌───────────────────────┐
│   User   │         │  MedicationAppointment│
│(receptor)│         │                       │
│          │         │  status               │
│          │         │  proposed_by          │
└──────────┘         └───────────────────────┘
     │                     │
     │                     │ 1
     │ 1                   ▼ 1
     │               ┌──────────────┐
     └──────────────►│ DoctorRating │
         receptor_id │              │
                     │ rating       │
                     │ comment      │
                     └──────────────┘
```

---

## User Flows

See `docs/BUSINESS_FLOWS.md` for detailed flow documentation.

### Summary

1. **Registration:** Doctor or Receptor registers → Admin approves → Full access granted
2. **Offering:** Doctor creates offering from drug catalog → Status: available
3. **Request:** Receptor searches and requests offering → Status: reserved
4. **Confirmation:** Doctor confirms or rejects request
5. **Appointment:** Either party proposes time → Negotiation → Both confirm time
6. **Delivery:** Both confirm handoff → Status: completed
7. **Rating:** Receptor evaluates doctor

---

## Business Rules

### User & Authentication

1. Email must be unique across all users
2. Phone number must be unique across all users
3. CPF must be valid (checksum) and unique per receptor
4. CRM + CRM_UF must be unique per doctor
5. Users must accept terms to register
6. Users start with `pending` status
7. Only `approved` users can access API endpoints
8. Admins can approve or reject pending users

### Medication Offerings

9. Only doctors can create medication offerings
10. Doctors can only edit/delete their own offerings
11. Cannot edit/delete offerings with confirmed requests
12. Expiration date must be in the future (max 10 years)
13. Quantity must be between 1 and 100,000

### Medication Requests

14. Only receptors can create requests
15. Cannot request your own offering (if doctor is also receptor)
16. Only one pending/confirmed request per offering allowed
17. Offering must be `available` to request
18. Creating request changes offering to `reserved`
19. Rejecting request changes offering back to `available`

### Appointments

20. Only participants can access appointment details
21. Either party can propose initial appointment
22. Counter-proposal resets other party's confirmation
23. Both parties must confirm for status change
24. Address must belong to the doctor

### Ratings

25. Only receptors can rate doctors
26. Only after appointment is `completed`
27. Only one rating per appointment
28. Rating (1-5) cannot be changed after creation
29. Comment can be updated
30. Ratings cannot be deleted

---

## Enums

### UserRole
```php
enum UserRole: string
{
    case DOCTOR = 'doctor';
    case RECEPTOR = 'receptor';
    case ADMIN = 'admin';
}
```

### UserStatus
```php
enum UserStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
```

### MedicationOfferingStatus
- `available` - Open for requests
- `reserved` - Has pending/confirmed request
- `completed` - Donation finalized

### MedicationRequestStatus
- `pending` - Awaiting doctor decision
- `confirmed` - Doctor approved
- `rejected` - Doctor declined

### MedicationAppointmentStatus
- `proposed` - Awaiting other party's response
- `confirmed` - Both parties agreed on time
- `completed` - Donation handed over
