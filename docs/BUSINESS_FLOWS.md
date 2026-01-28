# Business Flows

This document describes all business flows in DoaFarma, including user journeys, state transitions, and system behavior.

---

## System Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              DoaFarma Platform                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   ┌─────────┐        ┌──────────────┐        ┌─────────────┐              │
│   │  Doctor │───────>│   Offering   │<───────│   Receptor  │              │
│   └─────────┘        └──────────────┘        └─────────────┘              │
│        │                    │                       │                      │
│        │                    ▼                       │                      │
│        │             ┌──────────────┐               │                      │
│        │             │   Request    │<──────────────┘                      │
│        │             └──────────────┘                                      │
│        │                    │                                              │
│        │                    ▼                                              │
│        │             ┌──────────────┐                                      │
│        └────────────>│  Appointment │<──────────────────────────────────   │
│                      └──────────────┘                                      │
│                             │                                              │
│                             ▼                                              │
│                      ┌──────────────┐                                      │
│                      │    Rating    │                                      │
│                      └──────────────┘                                      │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 1. User Registration Flow

### 1.1 Doctor Registration

**Actors:** Doctor (new user), System, Admin

**Preconditions:** None

**Flow:**

```
1. Doctor provides registration data:
   - name (required)
   - email (required, unique)
   - password (required, min 8 chars)
   - phone_number (required, 10-11 digits)
   - crm (required, 6 digits)
   - crm_uf (required, 2 letter state code)
   - addresses (required, at least one)
   - terms_accepted (required, must be true)

2. System validates all fields:
   - Email format and uniqueness
   - CRM format and uniqueness
   - Phone format and uniqueness
   - Address completeness

3. System creates:
   - User record (role: 'doctor', status: 'pending')
   - Doctor record (linked to user)
   - Address records (linked to user)

4. Doctor receives auth token (can login but cannot use API)

5. Admin reviews registration in Filament panel

6. Admin approves or rejects:
   - Approved: User.status = 'approved', full API access
   - Rejected: User.status = 'rejected', no API access
```

**State Diagram:**

```
┌─────────┐     Admin      ┌──────────┐
│ Pending │───approves────>│ Approved │
└─────────┘                └──────────┘
     │
     │ Admin
     │ rejects
     ▼
┌──────────┐
│ Rejected │
└──────────┘
```

### 1.2 Receptor Registration

**Actors:** Receptor (new user), System, Admin

**Preconditions:** None

**Flow:**

```
1. Receptor provides registration data:
   - name (required)
   - email (required, unique)
   - cpf (required, valid Brazilian CPF, unique)
   - password (required, min 8 chars)
   - phone_number (required, 10-11 digits)
   - terms_accepted (required, must be true)

2. System validates all fields:
   - Email format and uniqueness
   - CPF format, checksum, and uniqueness
   - Phone format and uniqueness

3. System creates:
   - User record (role: 'receptor', status: 'pending')

4. Receptor receives auth token (can login but cannot use API)

5. Admin reviews and approves/rejects (same as doctor)
```

**CPF Validation:**
- 11 digits format: XXX.XXX.XXX-XX
- Checksum validation using standard algorithm
- CPF is encrypted at rest (cpf_hash for uniqueness check)

---

## 2. Medication Offering Flow

### 2.1 Creating an Offering

**Actors:** Doctor (approved), System

**Preconditions:**
- Doctor must be logged in
- Doctor.User.status must be 'approved'

**Flow:**

```
1. Doctor searches drug catalog:
   - Full-text search on product_name, substance, laboratory
   - Portuguese language stemming

2. Doctor selects a drug and provides:
   - lot_number (required, batch identifier)
   - expires_at (required, future date, max 10 years)
   - quantity (required, 1-100,000 units)

3. System validates:
   - Drug exists
   - Expiration is valid
   - Quantity is within limits

4. System creates MedicationOffering:
   - status: 'available'
   - doctor_id: current doctor
   - drug_id: selected drug
```

### 2.2 Managing Offerings

**Edit Offering:**
- Doctor can only edit their own offerings
- Can update: lot_number, expires_at, quantity
- Cannot change drug_id after creation

**Delete Offering:**
- Doctor can only delete their own offerings
- Deletes cascade to related requests (if any pending)

### 2.3 Offering States

```
┌───────────┐     Receptor creates      ┌──────────┐
│ Available │────────request───────────>│ Reserved │
└───────────┘                           └──────────┘
                                              │
                                              │ Delivery
                                              │ confirmed
                                              ▼
                                        ┌───────────┐
                                        │ Completed │
                                        └───────────┘
```

| State | Description | Allowed Actions |
|-------|-------------|-----------------|
| `available` | Open for requests | Receptors can create requests |
| `reserved` | Has pending/confirmed request | No new requests allowed |
| `completed` | Donation finalized | None (historical record) |

---

## 3. Medication Request Flow

### 3.1 Creating a Request

**Actors:** Receptor (approved), System

**Preconditions:**
- Receptor must be logged in and approved
- MedicationOffering.status must be 'available'

**Flow:**

```
1. Receptor browses/searches available offerings
   - Search by drug name, substance, or doctor name
   - Filter by location (future feature)

2. Receptor views offering details:
   - Drug information
   - Quantity available
   - Expiration date
   - Doctor information (name, rating)

3. Receptor creates request:
   - medication_offering_id (required)

4. System validates:
   - Offering exists and is available
   - No existing pending request from this receptor
   - Receptor is not the offering's doctor

5. System creates MedicationRequest:
   - status: 'pending'
   - receptor_id: current user

6. System updates MedicationOffering:
   - status: 'reserved'

7. System sends push notification to doctor
```

### 3.2 Processing Requests (Doctor)

**Confirm Request:**
```
1. Doctor views received requests
2. Doctor confirms request
3. System updates MedicationRequest.status = 'confirmed'
4. System sends push notification to receptor
5. Receptor can now propose an appointment
```

**Reject Request:**
```
1. Doctor views received requests
2. Doctor rejects request
3. System updates MedicationRequest.status = 'rejected'
4. System updates MedicationOffering.status = 'available'
5. System sends push notification to receptor
6. Offering becomes available for new requests
```

### 3.3 Request States

```
┌─────────┐     Doctor      ┌───────────┐
│ Pending │───confirms─────>│ Confirmed │
└─────────┘                 └───────────┘
     │
     │ Doctor
     │ rejects
     ▼
┌──────────┐
│ Rejected │
└──────────┘
```

| State | Description | Next Actions |
|-------|-------------|--------------|
| `pending` | Awaiting doctor decision | Doctor: confirm or reject |
| `confirmed` | Doctor approved | Either party: propose appointment |
| `rejected` | Doctor declined | None (historical record) |

---

## 4. Appointment Flow

### 4.1 Proposing an Appointment

**Actors:** Doctor or Receptor, System

**Preconditions:**
- MedicationRequest.status must be 'confirmed'
- No existing appointment for this request

**Flow:**

```
1. Either party proposes appointment:
   - medication_request_id (required)
   - scheduled_date (required, future date)
   - scheduled_time (required, business hours recommended)
   - address_id (optional, defaults to doctor's address)

2. System validates:
   - Request is confirmed
   - Date is in the future
   - Address belongs to the doctor

3. System creates MedicationAppointment:
   - status: 'proposed'
   - proposed_by: 'doctor' or 'receptor'
   - doctor_confirmed: true if proposed by doctor
   - receptor_confirmed: true if proposed by receptor

4. System sends push notification to other party
```

### 4.2 Negotiating Schedule

**Accept Proposal:**
```
1. Other party views proposed appointment
2. Accepts the proposed date/time
3. System updates:
   - doctor_confirmed = true (if receptor accepting)
   - receptor_confirmed = true (if doctor accepting)
   - status = 'confirmed' (both flags true)
4. Push notification sent confirming appointment
```

**Counter-Propose:**
```
1. Other party doesn't agree with proposed time
2. Submits new date/time (optionally new address)
3. System updates:
   - scheduled_date, scheduled_time, address_id
   - proposed_by = current party
   - Resets other party's confirmation flag
   - status remains 'proposed'
4. Push notification sent with new proposal
```

### 4.3 Confirming Delivery

**Flow:**
```
1. Both parties meet at scheduled time/place

2. Doctor confirms delivery:
   - PATCH /medication-appointments/{id}/confirm-delivery-doctor
   - doctor_confirmed = true

3. Receptor confirms receipt:
   - PATCH /medication-appointments/{id}/confirm-delivery-receptor
   - receptor_confirmed = true

4. When both confirmed:
   - MedicationAppointment.status = 'completed'
   - MedicationOffering.status = 'completed'
   - Push notifications sent to both parties

5. Receptor can now rate the doctor
```

### 4.4 Appointment States

```
┌──────────┐     Both parties     ┌───────────┐     Both confirm     ┌───────────┐
│ Proposed │───────accept────────>│ Confirmed │──────delivery───────>│ Completed │
└──────────┘                      └───────────┘                      └───────────┘
     ▲                                  │
     │                                  │
     └────────counter-propose───────────┘
```

| State | Description | Allowed Actions |
|-------|-------------|-----------------|
| `proposed` | One party proposed time | Accept or counter-propose |
| `confirmed` | Both agreed on time | Confirm delivery |
| `completed` | Donation handed over | Rate doctor |

---

## 5. Rating Flow

### 5.1 Rating a Doctor

**Actors:** Receptor, System

**Preconditions:**
- MedicationAppointment.status must be 'completed'
- Receptor participated in the appointment
- No existing rating for this appointment

**Flow:**

```
1. Receptor views completed appointment
2. Submits rating:
   - rating (required, 1-5 stars)
   - comment (optional, max 1000 chars)

3. System validates:
   - Appointment is completed
   - Receptor participated
   - No duplicate rating

4. System creates DoctorRating:
   - Links to appointment, doctor, receptor
   - Immutable after creation (can only update comment)

5. Doctor's average rating is updated
```

### 5.2 Viewing Ratings

**Doctor:**
- Can view all ratings received
- Cannot reply to ratings (future feature)

**Receptor:**
- Can view their own submitted ratings
- Can view any doctor's ratings when browsing offerings

**Rating Display:**
- Doctor's profile shows average rating and count
- Individual ratings show stars, comment, and date

---

## 6. Push Notification Events

| Event | Recipient | Message |
|-------|-----------|---------|
| New request created | Doctor | "Novo pedido de medicamento recebido" |
| Request confirmed | Receptor | "Seu pedido foi confirmado pelo médico" |
| Request rejected | Receptor | "Seu pedido foi recusado pelo médico" |
| Appointment proposed | Other party | "Nova proposta de agendamento" |
| Appointment counter-proposed | Other party | "Contraproposta de agendamento recebida" |
| Appointment confirmed | Both | "Agendamento confirmado" |
| Delivery confirmed | Both | "Entrega confirmada" |

---

## 7. Authorization Matrix

| Action | Doctor | Receptor | Admin |
|--------|--------|----------|-------|
| Create offering | Own only | - | View all |
| Edit/Delete offering | Own only | - | View all |
| Create request | - | Yes | View all |
| Confirm/Reject request | Own offerings | - | View all |
| Propose appointment | Confirmed requests | Confirmed requests | View all |
| Accept/Counter appointment | Participant | Participant | View all |
| Confirm delivery | Participant | Participant | View all |
| Rate doctor | - | After completion | View all |
| Approve users | - | - | Yes |
| View activity logs | - | - | Yes |

---

## 8. Error Scenarios

### Authentication Errors
| Scenario | HTTP Code | Error |
|----------|-----------|-------|
| Invalid credentials | 401 | "Credenciais inválidas" |
| Token expired | 401 | Token refresh required |
| User pending approval | 403 | "Aguardando aprovação do administrador" |
| User rejected | 403 | "Cadastro não aprovado" |

### Business Rule Violations
| Scenario | HTTP Code | Error |
|----------|-----------|-------|
| Request on reserved offering | 422 | "Esta oferta já está reservada" |
| Duplicate request | 422 | "Você já possui uma solicitação pendente" |
| Self-request (doctor requesting own) | 422 | "Você não pode solicitar sua própria oferta" |
| Edit others' offering | 403 | Policy denied |
| Rate without completing | 422 | "Agendamento não está completo" |

---

## 9. Data Retention

| Data Type | Retention | Notes |
|-----------|-----------|-------|
| User accounts | Indefinite | Soft delete available |
| Medication offerings | Indefinite | Historical record |
| Requests | Indefinite | Audit trail |
| Appointments | Indefinite | Audit trail |
| Ratings | Indefinite | Cannot be deleted |
| Activity logs | 2 years | LGPD compliance |
| Push tokens | Until logout | Cleaned on user logout |
