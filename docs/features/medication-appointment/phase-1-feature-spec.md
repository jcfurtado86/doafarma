# Phase 1: Feature Specification - Medication Appointment System

## Feature Overview

**Feature Name:** Medication Appointment System (Sistema de Agendamento de Entrega de Medicamentos)

**User Story:** As a receptor (patient), once my medication request is confirmed by the doctor, I want to schedule an appointment to pick up the medication at a convenient date and time.

**Business Context:**
This feature extends the medication request system by adding appointment scheduling functionality. After a doctor confirms a medication request, the system needs to coordinate when and where the medication handoff will occur. This creates a complete flow from requesting medications to confirming their delivery.

---

## Scope Definition

### In Scope

#### 1. Main Feature: Appointment Scheduling

1. **New Entity: MedicationAppointment**
   - Store appointment details (date, time, location, status)
   - Link to confirmed medication requests
   - Track delivery confirmation from both parties

2. **Backend API Endpoints:**
   - Create appointment (receptor proposes date/time when requesting OR after confirmation)
   - Update/reschedule appointment (if needed)
   - Confirm delivery by receptor (mark as completed)
   - Confirm delivery by doctor (mark as completed)
   - List appointments for doctor
   - List appointments for receptor

3. **Appointment Workflow:**
   - Receptor proposes date/time when request is confirmed
   - Doctor sees proposed date/time and can accept or suggest changes
   - Location is automatically selected (first doctor address, flexible for future changes)
   - Both parties can mark appointment as "completed" after meeting
   - Medication offering status updates to "completed" when both confirm delivery

4. **Mobile Frontend:**
   - Screen for receptor to propose appointment date/time
   - Calendar/date picker component
   - Time picker component
   - Appointment details view
   - Confirmation button for delivery completion
   - Visual appointment status indicators

#### 2. Bug Fixes: Role-Based Navigation

1. **Login Flow:**
   - Fix redirect after login to respect user role
   - Doctor → `/(auth)/dashboard`
   - Receptor → `/(auth)/receptor/search`

2. **Registration Flow:**
   - Fix redirect after doctor registration → `/(auth)/dashboard`
   - Fix redirect after receptor registration → `/(auth)/receptor/search`

### Out of Scope

1. **Advanced scheduling features:**
   - Recurring appointments
   - Multiple appointments per request
   - Calendar integration (Google Calendar, Apple Calendar)
   - Reminders/notifications (future feature)

2. **Communication features:**
   - In-app chat
   - SMS/email notifications
   - Direct messaging about rescheduling

3. **Complex availability system:**
   - Doctor's available time slots management
   - Conflict detection with other appointments
   - Automatic scheduling suggestions

4. **Delivery tracking:**
   - GPS location tracking
   - Real-time status updates
   - Proof of delivery (photos, signatures)

5. **Cancellation workflow:**
   - Appointment cancellation by either party (future consideration)
   - Cancellation reasons tracking

---

## Requirements

### Functional Requirements

#### FR1: Create Medication Appointment

**Trigger:** Receptor wants to schedule after request is confirmed

- **Actor:** Receptor (authenticated, request owner)
- **Preconditions:**
  - Medication request exists and status = 'confirmed'
  - No existing appointment for this request
- **Action:** POST /api/v1/medication-appointments
- **Input:**
  ```json
  {
    "medication_request_id": 123,
    "scheduled_date": "2026-01-15",
    "scheduled_time": "14:30",
    "address_id": 5  // doctor's address
  }
  ```
- **Validation:**
  - `scheduled_date` must be future date (not past)
  - `scheduled_time` must be valid time format (HH:mm)
  - `address_id` must belong to the doctor who owns the offering
  - Request must be in 'confirmed' status
- **Output:** Created appointment with status "scheduled"
- **Side Effects:**
  - Appointment record created
  - Medication request status updates to indicate appointment exists

#### FR2: List Appointments for Receptor

- **Actor:** Receptor (authenticated)
- **Action:** GET /api/v1/medication-appointments
- **Filter:** Appointments for authenticated receptor's requests
- **Output:** List of appointments with request, offering, and doctor details
- **Sorting:** Ordered by scheduled date/time (nearest first)

#### FR3: List Appointments for Doctor

- **Actor:** Doctor (authenticated)
- **Action:** GET /api/v1/medication-appointments/received
- **Filter:** Appointments for doctor's offerings
- **Output:** List of appointments with receptor and medication details
- **Sorting:** Ordered by scheduled date/time (nearest first)

#### FR4: Receptor Confirms Delivery

- **Actor:** Receptor (authenticated, appointment owner)
- **Preconditions:**
  - Appointment exists
  - Appointment status = 'scheduled'
  - Scheduled date/time has passed (or is current day)
- **Action:** PATCH /api/v1/medication-appointments/{id}/confirm-delivery-receptor
- **Output:** Appointment `receptor_confirmed` = true
- **Side Effects:**
  - If both parties confirmed → status = 'completed', offering status = 'completed'

#### FR5: Doctor Confirms Delivery

- **Actor:** Doctor (authenticated, offering owner)
- **Preconditions:**
  - Appointment exists
  - Appointment status = 'scheduled'
  - Scheduled date/time has passed (or is current day)
- **Action:** PATCH /api/v1/medication-appointments/{id}/confirm-delivery-doctor
- **Output:** Appointment `doctor_confirmed` = true
- **Side Effects:**
  - If both parties confirmed → status = 'completed', offering status = 'completed'

#### FR6: Fix Login Redirect

- **Current Bug:** All users redirect to medication search screen
- **Expected Behavior:**
  - Doctor login → `/(auth)/dashboard`
  - Receptor login → `/(auth)/receptor/search`
- **Files to Fix:** `mobile/app/login.tsx` (lines 86-93)

#### FR7: Fix Registration Redirects

- **Current Bugs:**
  - Doctor registration → redirects to dashboard (CORRECT - no fix needed)
  - Receptor registration → redirects to dashboard (WRONG - should go to search)
- **Expected Behavior:**
  - Doctor registration → `/(auth)/dashboard` ✓
  - Receptor registration → `/(auth)/receptor/search` ✗
- **Files to Fix:**
  - `mobile/screens/ReceptorRegistration/index.tsx` (line 125)

### Non-Functional Requirements

#### NFR1: Performance
- Appointment creation should respond within 500ms
- Date/time selection should be instant (client-side)
- List endpoints support pagination (default 15 items)

#### NFR2: Security
- All endpoints require authentication (Sanctum token)
- Receptors can only create appointments for their own confirmed requests
- Doctors can only confirm delivery for their own offerings
- Cannot modify appointments after scheduled time passes

#### NFR3: Data Integrity
- One appointment per medication request
- Cascade deletes if request is deleted
- Date/time validations prevent past appointments
- Atomic updates for dual confirmation logic

#### NFR4: Usability (Mobile)
- Intuitive date picker (Portuguese locale)
- Time picker with 15-minute intervals
- Clear appointment status indicators
- Error messages in Portuguese
- Loading states during API calls
- Confirmation dialogs for delivery confirmation

---

## Entities & Data Model

### New Entity: MedicationAppointment

| Field | Type | Description | Constraints |
|-------|------|-------------|-------------|
| id | bigint | Primary key | Auto-increment |
| medication_request_id | bigint | Associated request | FK to medication_requests, NOT NULL, UNIQUE |
| address_id | bigint | Pickup location | FK to addresses, NOT NULL |
| scheduled_date | date | Appointment date | NOT NULL, must be >= today |
| scheduled_time | time | Appointment time | NOT NULL |
| status | enum/string | Appointment status | Values: 'scheduled', 'completed', 'cancelled' |
| receptor_confirmed | boolean | Receptor delivery confirm | Default: false |
| doctor_confirmed | boolean | Doctor delivery confirm | Default: false |
| created_at | timestamp | Creation time | Auto |
| updated_at | timestamp | Last update | Auto |

**Relationships:**
- `medication_request_id` → `medication_requests.id` (belongs to MedicationRequest)
- `address_id` → `addresses.id` (belongs to Address)
- Through request: belongs to User (receptor) and MedicationOffering

**Indexes:**
- Primary key on `id`
- Unique index on `medication_request_id` (one appointment per request)
- Index on `scheduled_date` for date-based queries
- Composite index on `status, scheduled_date` for active appointments listing

**Business Rules:**
- Status transitions: `scheduled → completed` (when both confirm)
- Cannot create appointment if request is not confirmed
- Cannot confirm delivery before scheduled date/time
- When both `receptor_confirmed` and `doctor_confirmed` = true:
  - Appointment status → 'completed'
  - Medication offering status → 'completed'

### Modified Entity: MedicationRequest

**New relationship:**
- `hasOne(MedicationAppointment::class)` - request has one appointment

**No new fields needed** - relationship only

### Modified Entity: MedicationOffering

**Status values update:**
- Existing: 'available', 'reserved'
- New: 'completed' (when appointment is completed)

---

## User Flows

### Flow 1: Receptor Schedules Appointment

1. Receptor opens app, navigates to "My Requests" or "Appointments"
2. Sees confirmed request without appointment
3. Taps "Schedule Pickup" button
4. Sees appointment form:
   - Doctor's addresses list (select one)
   - Date picker (calendar view, min date = today)
   - Time picker (dropdown or scroll, 15-min intervals)
5. Selects date, time, and address
6. Taps "Confirm Appointment"
7. App shows confirmation dialog: "Schedule for Jan 15, 2026 at 14:30?"
8. Receptor confirms
9. App calls API: POST /api/v1/medication-appointments
10. Backend validates, creates appointment
11. App shows success: "Appointment scheduled! See you on Jan 15 at 14:30"
12. Appointment appears in "My Appointments" list

### Flow 2: Doctor Views Appointments

1. Doctor opens app, navigates to "Appointments" or "Schedule"
2. Sees list of upcoming appointments:
   - Date, time
   - Receptor name and phone
   - Medication details
   - Location (address)
3. Can filter by date or status
4. Taps appointment to see full details

### Flow 3: Delivery Confirmation (Receptor)

1. After appointment date/time, receptor opens app
2. Navigates to "My Appointments"
3. Sees appointment with status "Scheduled"
4. Taps "Confirm Delivery Received" button
5. App calls API: PATCH /api/v1/medication-appointments/{id}/confirm-delivery-receptor
6. App shows: "Delivery confirmed! Waiting for doctor confirmation."
7. If doctor already confirmed → status becomes "Completed"

### Flow 4: Delivery Confirmation (Doctor)

1. After appointment, doctor opens app
2. Navigates to "Appointments"
3. Sees appointment with status "Scheduled"
4. Taps "Confirm Delivery Completed" button
5. App calls API: PATCH /api/v1/medication-appointments/{id}/confirm-delivery-doctor
6. App shows: "Delivery confirmed!"
7. If receptor already confirmed → status becomes "Completed"
8. Medication offering status updates to "Completed"

### Flow 5: Fixed Login Redirect

1. User enters email and password
2. Taps "Login"
3. Backend authenticates and returns user with role
4. **If role = 'doctor':**
   - App redirects to `/(auth)/dashboard`
   - Shows doctor's offerings, requests, appointments
5. **If role = 'receptor':**
   - App redirects to `/(auth)/receptor/search`
   - Shows medication search screen

### Flow 6: Fixed Receptor Registration Redirect

1. Receptor fills registration form
2. Taps "Create Account"
3. Backend creates user with role = 'receptor'
4. App receives auth token
5. App redirects to `/(auth)/receptor/search` (FIXED)
6. Shows medication search screen (not dashboard)

---

## Business Rules

### BR1: Appointment Creation Rules
- Only receptors can create appointments
- Appointment can only be created for confirmed requests
- One appointment per request (unique constraint)
- Scheduled date must be today or future
- Location must be one of the doctor's registered addresses

### BR2: Delivery Confirmation Rules
- Both doctor and receptor must confirm delivery
- Confirmation only possible on or after scheduled date
- Either party can confirm first
- When both confirm:
  - Appointment status → 'completed'
  - Medication offering status → 'completed'
- Cannot un-confirm after marking as delivered

### BR3: Status Transitions

**MedicationAppointment status flow:**
```
scheduled → completed (when both parties confirm)
```

**MedicationOffering status flow (updated):**
```
available → reserved (request created)
reserved → available (request rejected)
reserved → completed (appointment delivery confirmed by both)
```

### BR4: Authorization
- Receptors can only create/view appointments for their own requests
- Doctors can only view appointments for their own offerings
- No public access to appointments
- Delivery confirmation restricted to respective party

### BR5: Location Selection (Design Decision)
- For MVP: Use doctor's first address automatically
- Future: Allow receptor to choose from doctor's addresses
- Future: Doctor can specify preferred address per offering

---

## Acceptance Criteria

### AC1: Appointment Creation
- [ ] Receptor can create appointment for confirmed request
- [ ] Appointment includes date, time, and location
- [ ] Date must be today or future (past dates rejected)
- [ ] Time must be valid format (HH:mm)
- [ ] Location automatically selected from doctor's addresses
- [ ] Cannot create appointment for unconfirmed request
- [ ] Cannot create duplicate appointment for same request

### AC2: Appointment Listing for Receptor
- [ ] Receptor sees list of their appointments
- [ ] Each appointment shows: date, time, location, doctor info, medication details
- [ ] Appointments sorted by date (nearest first)
- [ ] Status indicator visible (scheduled/completed)

### AC3: Appointment Listing for Doctor
- [ ] Doctor sees appointments for their offerings
- [ ] Each appointment shows: date, time, receptor info, medication details
- [ ] Can filter or sort by date
- [ ] Status indicator visible

### AC4: Delivery Confirmation by Receptor
- [ ] Receptor can confirm delivery after appointment date
- [ ] Cannot confirm before scheduled date
- [ ] Status updates correctly
- [ ] If doctor already confirmed → appointment completes

### AC5: Delivery Confirmation by Doctor
- [ ] Doctor can confirm delivery after appointment date
- [ ] Cannot confirm before scheduled date
- [ ] Status updates correctly
- [ ] If receptor already confirmed → appointment completes
- [ ] Medication offering status updates to 'completed'

### AC6: Dual Confirmation Logic
- [ ] Appointment needs both confirmations to complete
- [ ] Order doesn't matter (either can confirm first)
- [ ] When both confirm: status = 'completed', offering = 'completed'

### AC7: Bug Fix - Login Redirect
- [ ] Doctor login redirects to `/(auth)/dashboard`
- [ ] Receptor login redirects to `/(auth)/receptor/search`
- [ ] Redirect happens immediately after successful authentication

### AC8: Bug Fix - Registration Redirect
- [ ] Doctor registration redirects to `/(auth)/dashboard` (already correct)
- [ ] Receptor registration redirects to `/(auth)/receptor/search` (FIXED)

### AC9: Mobile UI/UX
- [ ] Date picker with Portuguese locale
- [ ] Time picker with 15-minute intervals
- [ ] Clear appointment status badges
- [ ] Delivery confirmation button visible only after appointment date
- [ ] Loading states during API calls
- [ ] Success/error messages in Portuguese
- [ ] Confirmation dialogs before delivery confirmation

---

## Edge Cases & Error Handling

### E1: Past Date Selection
**Scenario:** Receptor tries to schedule appointment in the past
**Solution:** Client-side validation disables past dates, server-side validation rejects
**Response:** 422 Unprocessable Entity: "A data deve ser hoje ou no futuro."

### E2: Request Deleted Before Appointment
**Scenario:** Medication request deleted while appointment exists
**Solution:** Cascade delete removes appointment
**Response:** N/A (appointment no longer exists)

### E3: Doctor Deletes Address
**Scenario:** Doctor deletes address that is used in scheduled appointment
**Solution:** Prevent deletion if address is referenced by appointments, or cascade to cancel appointment
**Decision:** Prevent deletion (better UX)
**Response:** 409 Conflict: "Este endereço não pode ser removido pois há agendamentos ativos."

### E4: Duplicate Appointment Creation
**Scenario:** Receptor tries to create second appointment for same request
**Solution:** Unique constraint on medication_request_id
**Response:** 409 Conflict: "Já existe um agendamento para esta solicitação."

### E5: Early Delivery Confirmation
**Scenario:** User tries to confirm delivery before appointment date
**Solution:** Server-side validation checks if scheduled date has passed
**Response:** 422 Unprocessable Entity: "A entrega só pode ser confirmada após a data agendada."

### E6: Unconfirmed Request Appointment
**Scenario:** Receptor tries to create appointment for pending/rejected request
**Solution:** Validation checks request status = 'confirmed'
**Response:** 422 Unprocessable Entity: "Apenas solicitações confirmadas podem ter agendamento."

### E7: Invalid Address Selection
**Scenario:** Receptor selects address that doesn't belong to doctor
**Solution:** Validation ensures address belongs to offering's doctor
**Response:** 422 Unprocessable Entity: "Endereço inválido."

---

## Constraints & Assumptions

### Constraints
1. Single appointment per medication request
2. Location must be one of doctor's registered addresses
3. No rescheduling in MVP (delete and create new if needed)
4. No cancellation workflow in MVP
5. Delivery confirmation only after scheduled date

### Assumptions
1. Doctor's first address is acceptable for MVP location selection
2. Both parties will confirm delivery in good faith
3. 15-minute time intervals are sufficient granularity
4. No time zone handling needed (assume all users in same timezone)
5. Appointments are independent (no overlap validation needed for MVP)

### Design Decisions (for future flexibility)
1. **Location Selection:** Currently using first address, but `address_id` stored explicitly to allow future changes without data migration
2. **Confirmation Mechanism:** Dual confirmation tracked separately (`receptor_confirmed`, `doctor_confirmed`) to allow independent actions
3. **Status Field:** Separate from confirmation booleans to allow future statuses ('rescheduled', 'cancelled')

---

## Success Metrics

### Quantitative
- Number of appointments created
- Appointment completion rate (both confirmed / total scheduled)
- Average time from request confirmation to appointment scheduling
- Percentage of appointments confirmed on scheduled date

### Qualitative
- User feedback on scheduling flow clarity
- Ease of date/time selection
- Reduction in coordination errors between doctors and receptors

---

## Dependencies

### Technical Dependencies
- Laravel Sanctum (authentication)
- PostgreSQL (database)
- React Native DateTimePicker or similar (date/time selection)
- Expo Router (navigation)
- Zustand (state management)

### Data Dependencies
- Existing `medication_requests` table with 'confirmed' status
- Existing `addresses` table linked to doctors
- Existing `medication_offerings` table with status field

### Feature Dependencies
- Medication request system must be working
- Doctor address registration must be functional
- Authentication and role-based access control

---

## Open Questions & Decisions Made

### Q1: Where should the appointment be scheduled?
**Decision:** Use doctor's first registered address automatically for MVP. Store `address_id` explicitly for future flexibility (allow receptor to choose or doctor to specify per offering).

### Q2: Who proposes the date/time?
**Decision:** Receptor proposes date/time when creating appointment. Doctor sees proposed time and meets at that time. (Simple, receptor-driven flow)

### Q3: When does appointment creation happen?
**Decision:** Appointment is created separately after request is confirmed. Receptor accesses confirmed request and creates appointment in a dedicated flow.

### Q4: Is delivery confirmation needed?
**Decision:** Yes, both doctor and receptor must confirm delivery. This ensures accountability and properly closes the donation flow.

---

## Technical Notes

### Bug Analysis: Login/Registration Redirects

**Current Issue (Login):**
- File: `mobile/app/login.tsx`
- Lines 86-93
- Problem: All users redirect to `/dashboard` or `/receptor/search` but logic appears correct
- **FOUND:** Logic is actually CORRECT in login.tsx - bug must be elsewhere or already fixed

**Current Issue (Receptor Registration):**
- File: `mobile/screens/ReceptorRegistration/index.tsx`
- Line 125: `router.replace('/(auth)/dashboard');`
- Problem: Should redirect to `/(auth)/receptor/search` for receptors
- **Fix needed:** Change line 125 to use role-based redirect

**Current Issue (Doctor Registration):**
- File: `mobile/screens/DoctorRegistration/DoctorRegistrationFlow/index.tsx`
- Line 55: `router.replace('/(auth)/dashboard');`
- **This is CORRECT** - doctors should go to dashboard

---

## Glossary

- **Appointment:** Scheduled meeting between doctor and receptor for medication pickup
- **Scheduled Date/Time:** When the medication pickup will occur
- **Delivery Confirmation:** Action where doctor or receptor confirms medication was handed over
- **Completion:** Status when both parties have confirmed delivery
- **Location/Address:** Physical place where medication pickup happens

---

## Sign-off

**Phase 1 Status:** ✅ Complete

**Next Phase:** Solution Design (Phase 2)

**Key Decisions Made:**
1. Location: Use doctor's first address (flexible design for future)
2. Scheduling: Receptor proposes date/time
3. Confirmation: Dual confirmation required (both parties)
4. Bug fixes identified and scoped

**Reviewed By:** Awaiting user confirmation
