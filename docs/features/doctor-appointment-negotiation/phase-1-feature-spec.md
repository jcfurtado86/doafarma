# Phase 1: Feature Specification

## Feature: Doctor Appointment Negotiation

**Feature Slug:** `doctor-appointment-negotiation`
**Created:** 2026-01-17
**Status:** Approved

---

## 1. User Story

> **As a doctor**, I want to negotiate a meeting appointment with the receptor so that I can deliver the medication safely and conveniently.

---

## 2. Context

### Current State

The system currently supports:
1. Receptor requests medication (MedicationRequest: `pending`)
2. Doctor confirms or rejects request (`confirmed` / `rejected`)
3. **Receptor creates appointment** with date/time (MedicationAppointment: `scheduled`)
4. Doctor views received appointments
5. Both parties confirm delivery → appointment `completed`

### Problem

- Doctor has no way to negotiate the appointment
- If the proposed date/time doesn't work, there's no counter-proposal mechanism
- Doctor cannot choose which address to use for delivery
- The appointment status `scheduled` doesn't distinguish between "proposed" and "mutually agreed"

---

## 3. Proposed Solution

Enable a **negotiation flow** where both parties can propose and counter-propose dates until mutual agreement.

### Flow Summary

```
1. Doctor confirms medication request
2. Receptor proposes date/time (uses doctor's first address by default)
3. Doctor either:
   a) ACCEPTS → appointment confirmed
   b) COUNTER-PROPOSES → new date/time/address, awaits receptor
4. If counter-proposed, receptor either:
   a) ACCEPTS → appointment confirmed
   b) COUNTER-PROPOSES → new date/time, awaits doctor
5. Cycle continues until one party accepts
6. Once confirmed, existing delivery confirmation flow applies
```

---

## 4. Functional Requirements

### FR-01: Doctor Can Accept Appointment
- **Given** an appointment with `status: proposed` and `proposed_by: receptor`
- **When** the doctor accepts the appointment
- **Then** the status changes to `confirmed`

### FR-02: Doctor Can Counter-Propose Appointment
- **Given** an appointment with `status: proposed` and `proposed_by: receptor`
- **When** the doctor counter-proposes with new date/time/address
- **Then** the appointment is updated with new values
- **And** `proposed_by` changes to `doctor`
- **And** `status` remains `proposed`

### FR-03: Receptor Can Accept Counter-Proposal
- **Given** an appointment with `status: proposed` and `proposed_by: doctor`
- **When** the receptor accepts the appointment
- **Then** the status changes to `confirmed`

### FR-04: Receptor Can Counter-Propose Again
- **Given** an appointment with `status: proposed` and `proposed_by: doctor`
- **When** the receptor counter-proposes with new date/time
- **Then** the appointment is updated with new values
- **And** `proposed_by` changes to `receptor`
- **And** `status` remains `proposed`

### FR-05: Doctor Can Choose Address
- **Given** the doctor has multiple addresses registered
- **When** the doctor counter-proposes an appointment
- **Then** the doctor can select which address to use for delivery

### FR-06: Receptor Cannot Choose Address
- **Given** the receptor is creating or counter-proposing an appointment
- **When** they submit the proposal
- **Then** the address remains unchanged (doctor's choice or default)

### FR-07: Unlimited Counter-Proposals
- **Given** an appointment in `proposed` status
- **When** either party counter-proposes
- **Then** there is no limit to the number of counter-proposals

---

## 5. Non-Functional Requirements

### NFR-01: Backward Compatibility
- Existing appointments with `status: scheduled` must continue to work
- Migration must convert `scheduled` → `proposed` with `proposed_by: receptor`

### NFR-02: Data Integrity
- Only one active appointment per medication request (existing constraint)
- Address must belong to the doctor of the medication offering

### NFR-03: Authorization
- Only the party who did NOT propose can accept or counter-propose
- Doctor can only act on appointments for their own offerings
- Receptor can only act on their own appointments

---

## 6. Data Model Changes

### MedicationAppointment Table

| Field | Type | Change |
|-------|------|--------|
| `status` | enum | **MODIFY**: Add `proposed`, `confirmed` (keep `completed`) |
| `proposed_by` | enum | **NEW**: `receptor` or `doctor` |

### Status Transition Rules

```
                    ┌─────────────────────────────────────┐
                    │                                     │
                    ▼                                     │
┌──────────┐    ┌──────────┐    ┌───────────┐    ┌───────────┐
│ (create) │───▶│ proposed │───▶│ confirmed │───▶│ completed │
└──────────┘    └──────────┘    └───────────┘    └───────────┘
                    │
                    │ counter-propose
                    └─────────────────┘
```

---

## 7. API Changes

### New Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `PATCH` | `/api/v1/medication-appointments/{id}/accept` | Accept proposed appointment |
| `PATCH` | `/api/v1/medication-appointments/{id}/counter-propose` | Counter-propose new date/time/address |

### Modified Behavior

| Endpoint | Change |
|----------|--------|
| `POST /api/v1/medication-appointments` | Creates with `status: proposed`, `proposed_by: receptor` |
| `GET /api/v1/medication-appointments` | Include `proposed_by` in response |
| `GET /api/v1/medication-appointments/received` | Include `proposed_by` in response |

---

## 8. Scope Definition

### In Scope

- [x] Doctor accepts appointment proposal
- [x] Doctor counter-proposes with new date/time/address
- [x] Receptor accepts doctor's counter-proposal
- [x] Receptor counter-proposes again
- [x] Doctor chooses address when counter-proposing
- [x] New status flow: `proposed` → `confirmed` → `completed`
- [x] New field: `proposed_by`
- [x] Migration for existing data

### Out of Scope

- [ ] Notifications (push, email, SMS) - future feature
- [ ] Doctor initiating appointment without receptor proposal - future feature
- [ ] History of previous proposals - future feature
- [ ] Appointment cancellation - future feature
- [ ] Limit on number of counter-proposals - not needed
- [ ] Conflict resolution for simultaneous edits - rare edge case

---

## 9. Acceptance Criteria

1. **AC-01**: Doctor can view pending appointments awaiting their response
2. **AC-02**: Doctor can accept a receptor's proposed appointment
3. **AC-03**: Doctor can counter-propose with different date/time/address
4. **AC-04**: Receptor can view appointments awaiting their response
5. **AC-05**: Receptor can accept doctor's counter-proposal
6. **AC-06**: Receptor can counter-propose again
7. **AC-07**: Appointment shows `confirmed` status after acceptance
8. **AC-08**: Existing delivery confirmation flow works after `confirmed`
9. **AC-09**: Existing data is migrated correctly

---

## 10. Glossary

| Term | Definition |
|------|------------|
| **Proposal** | A suggested date/time/address for the appointment |
| **Counter-proposal** | A new proposal that replaces the previous one |
| **Proposer** | The party who made the current/last proposal |
| **Responder** | The party who needs to accept or counter-propose |
| **Accept** | Agree to the current proposal, confirming the appointment |

---

## 11. Open Questions

*None - all questions resolved during clarification.*

---

## 12. Decision Log

| Decision | Rationale |
|----------|-----------|
| Receptor initiates, doctor counter-proposes | Maintains current flow, receptor has urgency |
| No proposal history | Simplicity, current state is what matters |
| Unilateral acceptance | Proposer implicitly accepts, reduces friction |
| Doctor chooses address | Flexibility for multiple locations |
| Unlimited counter-proposals | No clear reason to limit |
| Last-write-wins for conflicts | Rare edge case, not worth complexity |
