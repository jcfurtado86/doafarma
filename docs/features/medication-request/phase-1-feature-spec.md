# Phase 1: Feature Specification - Medication Request System

## Feature Overview

**Feature Name:** Medication Request System
**User Story:** As a receptor (patient), I want to request specific medications available on the platform so they will be reserved for me.

**Business Context:**
This feature enables patients (receptors) to browse available medication offerings posted by doctors and request specific medications. When a receptor requests a medication, it becomes reserved, preventing other users from requesting it. This initiates the communication process between doctor and patient to arrange medication pickup.

---

## Scope Definition

### In Scope

1. **Backend API:**
   - Create a new `MedicationRequest` entity/model
   - Endpoint to create a medication request (receptor requests a medication offering)
   - Endpoint to list requests for a receptor (view my requests)
   - Endpoint to list requests for a doctor (view requests for my offerings)
   - Endpoint for doctor to confirm/accept a request
   - Endpoint for doctor to reject a request
   - Update medication offering status when requested/reserved
   - Authorization policies (only receptors can request, only offering owner can confirm/reject)

2. **Mobile Frontend:**
   - Screen/component to display available medication offerings
   - Button/action to request a medication
   - Screen to view receptor's active requests
   - Screen for doctor to view requests for their offerings
   - Actions for doctor to confirm/reject requests
   - Visual indication of offering status (available, reserved, completed)

3. **Business Logic:**
   - A medication offering can only be requested if it's available (not already reserved)
   - When a request is created, the offering becomes "reserved"
   - Only one active request per offering at a time
   - Doctor can confirm (accept) a request, marking it as confirmed
   - Doctor can reject a request, freeing the offering to become available again
   - Receptors can only request medications, not their own offerings (receptors don't create offerings)

### Out of Scope

1. **Communication features** between doctor and patient (chat, messaging)
2. **Delivery/pickup scheduling** - appointment booking system
3. **Confirmation of medication handoff** - final delivery confirmation workflow
4. **Notification system** - push notifications (future consideration)
5. **Request cancellation by receptor** - simplified flow for MVP
6. **Request history** - viewing completed/rejected requests (can be added later)
7. **Multiple quantity requests** - receptor requests the full offering quantity

---

## Requirements

### Functional Requirements

#### FR1: Create Medication Request
- **Actor:** Receptor (authenticated user with role=receptor)
- **Preconditions:**
  - User is authenticated
  - User has role "receptor"
  - Medication offering exists and is available (status != reserved)
- **Action:** POST /api/v1/medication-requests
- **Input:** `medication_offering_id`
- **Validation:**
  - `medication_offering_id` must exist
  - Offering must not already have an active request
  - User must be a receptor
  - User cannot request their own offering (if somehow created)
- **Output:** Created request with status "pending"
- **Side Effects:**
  - Medication offering status changes to "reserved"
  - Request record created

#### FR2: List Receptor's Requests
- **Actor:** Receptor (authenticated)
- **Action:** GET /api/v1/medication-requests
- **Filter:** Authenticated user's requests only
- **Output:** List of requests with offering details (drug, doctor, status)

#### FR3: List Doctor's Received Requests
- **Actor:** Doctor (authenticated user with role=doctor)
- **Action:** GET /api/v1/medication-requests/received
- **Filter:** Requests for offerings owned by authenticated doctor
- **Output:** List of requests with receptor details and offering info

#### FR4: Doctor Confirms Request
- **Actor:** Doctor (authenticated, offering owner)
- **Preconditions:**
  - Request exists
  - Request belongs to doctor's offering
  - Request status is "pending"
- **Action:** PATCH /api/v1/medication-requests/{id}/confirm
- **Output:** Request status changes to "confirmed"
- **Side Effects:** Offering remains reserved

#### FR5: Doctor Rejects Request
- **Actor:** Doctor (authenticated, offering owner)
- **Preconditions:**
  - Request exists
  - Request belongs to doctor's offering
  - Request status is "pending"
- **Action:** PATCH /api/v1/medication-requests/{id}/reject
- **Output:** Request status changes to "rejected"
- **Side Effects:** Offering status returns to "available"

### Non-Functional Requirements

#### NFR1: Performance
- Request creation should respond within 500ms
- List endpoints should support pagination (default 15 items)

#### NFR2: Security
- All endpoints require authentication (Sanctum token)
- Authorization policies enforce role-based access
- Receptors cannot modify requests after creation
- Only offering owner can confirm/reject requests

#### NFR3: Data Integrity
- No two active requests for the same offering
- Database constraints prevent race conditions
- Cascade deletes if offering or user is deleted

#### NFR4: Usability (Mobile)
- Clear visual distinction between available/reserved offerings
- Intuitive request flow (single tap to request)
- Error messages in Portuguese
- Loading states during API calls

---

## Entities & Data Model

### New Entity: MedicationRequest

| Field | Type | Description | Constraints |
|-------|------|-------------|-------------|
| id | bigint | Primary key | Auto-increment |
| receptor_id | bigint | User who requested | FK to users, NOT NULL |
| medication_offering_id | bigint | Offering requested | FK to medication_offerings, NOT NULL |
| status | enum/string | Request status | Values: 'pending', 'confirmed', 'rejected' |
| created_at | timestamp | Request creation time | Auto |
| updated_at | timestamp | Last update time | Auto |

**Relationships:**
- `receptor_id` → `users.id` (belongs to User)
- `medication_offering_id` → `medication_offerings.id` (belongs to MedicationOffering)

**Indexes:**
- Primary key on `id`
- Index on `receptor_id` for user's requests listing
- Index on `medication_offering_id` for offering's requests
- Unique constraint on `medication_offering_id` WHERE `status = 'pending'` (only one active request)

### Modified Entity: MedicationOffering

**New field:**
| Field | Type | Description | Constraints |
|-------|------|-------------|-------------|
| status | enum/string | Offering availability | Values: 'available', 'reserved', 'completed' |

**Default:** 'available'

**New Relationship:**
- `hasMany(MedicationRequest::class)` - offering can have multiple requests (historical)
- `hasOne(MedicationRequest::class)` where status = 'pending' - active request

---

## User Flows

### Flow 1: Receptor Requests Medication

1. Receptor opens app, navigates to "Search Medications" or "Browse Offerings"
2. Searches or browses available offerings
3. Sees offering card with drug details, doctor info, expiry, quantity
4. Taps "Request Medication" button
5. App shows confirmation dialog: "Request this medication?"
6. Receptor confirms
7. App calls API: POST /api/v1/medication-requests
8. Backend validates, creates request, updates offering status
9. App shows success message: "Medication requested! Wait for doctor confirmation."
10. Offering status changes to "Reserved" in UI

### Flow 2: Doctor Views and Confirms Request

1. Doctor opens app, navigates to "Requests" or "My Offerings"
2. Sees list of received requests with receptor details
3. Selects a request to view details
4. Reviews receptor info, medication details
5. Taps "Confirm Request" button
6. App calls API: PATCH /api/v1/medication-requests/{id}/confirm
7. Backend updates request status to "confirmed"
8. App shows success: "Request confirmed! Contact the patient to arrange pickup."
9. Request status shows as "Confirmed"

### Flow 3: Doctor Rejects Request

1. Doctor views request (same as Flow 2)
2. Taps "Reject Request" button
3. App calls API: PATCH /api/v1/medication-requests/{id}/reject
4. Backend updates request to "rejected", offering to "available"
5. App shows: "Request rejected. Offering is now available again."
6. Offering becomes available for other receptors

---

## Business Rules

### BR1: Single Active Request
- A medication offering can have only ONE pending request at a time
- If a request is rejected, offering becomes available again
- If a request is confirmed, offering remains reserved (future: marked completed after handoff)

### BR2: Role-Based Actions
- Only receptors can create medication requests
- Only doctors who own the offering can confirm/reject requests
- Receptors cannot request medications they don't need (no quantity selection - full offering)

### BR3: Status Transitions

**MedicationRequest status flow:**
```
pending → confirmed (doctor confirms)
pending → rejected (doctor rejects)
```

**MedicationOffering status flow:**
```
available → reserved (request created)
reserved → available (request rejected)
reserved → completed (future: after handoff confirmed)
```

### BR4: Authorization
- Authenticated users only
- Receptors can only view their own requests
- Doctors can only view requests for their offerings
- No public access to requests

---

## Acceptance Criteria

### AC1: Medication Request Creation
- [ ] Receptor can request an available medication offering
- [ ] Request is created with status "pending"
- [ ] Offering status changes to "reserved"
- [ ] Request includes receptor ID and offering ID
- [ ] Cannot request an already reserved offering (validation error)
- [ ] Cannot request if not a receptor (authorization error)

### AC2: Request Listing for Receptor
- [ ] Receptor sees list of their requests
- [ ] Each request shows offering details (drug name, doctor)
- [ ] Request status is visible (pending/confirmed/rejected)
- [ ] Pagination works correctly

### AC3: Request Listing for Doctor
- [ ] Doctor sees requests for their offerings only
- [ ] Each request shows receptor details
- [ ] Can filter by status (pending/confirmed/rejected)

### AC4: Doctor Confirms Request
- [ ] Doctor can confirm a pending request
- [ ] Request status changes to "confirmed"
- [ ] Offering remains reserved
- [ ] Only offering owner can confirm
- [ ] Cannot confirm non-pending request

### AC5: Doctor Rejects Request
- [ ] Doctor can reject a pending request
- [ ] Request status changes to "rejected"
- [ ] Offering status returns to "available"
- [ ] Only offering owner can reject
- [ ] Cannot reject non-pending request

### AC6: Mobile UI/UX
- [ ] Available offerings clearly distinguished from reserved
- [ ] Request button disabled for reserved offerings
- [ ] Loading states during API calls
- [ ] Success/error messages in Portuguese
- [ ] Request status badges visible (Pending, Confirmed, Rejected)

---

## Edge Cases & Error Handling

### E1: Race Condition - Simultaneous Requests
**Scenario:** Two receptors request the same offering at the same time
**Solution:** Database unique constraint + transaction isolation ensures only one succeeds
**Response:** Second request gets 409 Conflict error: "Esta oferta já foi reservada."

### E2: Offering Deleted Before Request Confirmed
**Scenario:** Doctor deletes offering while request is pending
**Solution:** Cascade delete removes request
**Response:** N/A (request no longer exists)

### E3: Doctor Account Deleted
**Scenario:** Doctor account deleted with pending requests
**Solution:** Cascade delete removes offerings and requests
**Response:** N/A

### E4: Receptor Tries to Request Own Offering
**Scenario:** Edge case if receptor somehow has doctor role
**Solution:** Business logic validation prevents this
**Response:** 403 Forbidden: "Você não pode solicitar suas próprias ofertas."

### E5: Request Non-Existent Offering
**Scenario:** Invalid medication_offering_id
**Solution:** Validation rule checks existence
**Response:** 422 Unprocessable Entity: "A oferta de medicamento não existe."

### E6: Doctor Tries to Create Request
**Scenario:** User with role=doctor attempts to create request
**Solution:** Authorization check in policy/middleware
**Response:** 403 Forbidden: "Apenas pacientes podem solicitar medicamentos."

---

## Constraints & Assumptions

### Constraints
1. No partial quantity requests - receptor requests the full offering
2. No request cancellation by receptor in this version
3. No direct messaging between doctor and receptor (future feature)
4. Request history (rejected/confirmed) is stored but not prioritized in UI

### Assumptions
1. Receptors are motivated to request medications they need
2. Doctors will respond to requests in a reasonable timeframe
3. No automated expiration of pending requests (future consideration)
4. Phone numbers in user profiles can be used for out-of-app communication
5. Both doctor and receptor understand the platform workflow

---

## Success Metrics

### Quantitative
- Number of medication requests created
- Request confirmation rate (confirmed / total)
- Average time from request to confirmation
- Number of rejected requests

### Qualitative
- User feedback on request flow clarity
- Error rate during request creation
- Mobile app usability for request management

---

## Dependencies

### Technical Dependencies
- Laravel Sanctum (authentication)
- PostgreSQL (database with constraint support)
- React Native Expo (mobile frontend)
- Zustand (state management)

### Data Dependencies
- Existing `users` table with `role` field
- Existing `medication_offerings` table
- Existing `doctors` table

### Feature Dependencies
- User authentication must be working
- Medication offering search/browse feature (recently implemented)
- Doctor and receptor registration flows

---

## Glossary

- **Receptor:** Patient user who receives medication donations
- **Doctor:** Medical professional who donates medications
- **Medication Offering:** A doctor's posted medication available for donation
- **Request:** Receptor's action to reserve a specific medication offering
- **Status (Request):** pending | confirmed | rejected
- **Status (Offering):** available | reserved | completed

---

## Open Questions

None at this time. All requirements are clear based on the user's description.

---

## Sign-off

**Phase 1 Status:** Complete
**Next Phase:** Solution Design (Phase 2)
**Reviewed By:** Awaiting user confirmation
