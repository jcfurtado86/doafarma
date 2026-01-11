# Phase 2: Solution Design - Medication Request System

## Overview

This document defines the technical architecture and implementation approach for the Medication Request feature, following the specifications from Phase 1.

---

## Architecture Summary

### New Components

| Layer | Component | Purpose |
|-------|-----------|---------|
| Model | `MedicationRequest` | Entity for tracking medication requests |
| Migration | `create_medication_requests_table` | Database schema for requests |
| Migration | `add_status_to_medication_offerings` | Add status field to offerings |
| Policy | `MedicationRequestPolicy` | Authorization rules |
| Actions | `CreateMedicationRequestAction` | Business logic for creating requests |
| Actions | `ListReceptorRequestsAction` | List receptor's own requests |
| Actions | `ListDoctorRequestsAction` | List requests for doctor's offerings |
| Actions | `ConfirmMedicationRequestAction` | Doctor confirms request |
| Actions | `RejectMedicationRequestAction` | Doctor rejects request |
| Controllers | `StoreController` | POST /medication-requests |
| Controllers | `ListController` | GET /medication-requests |
| Controllers | `ReceivedController` | GET /medication-requests/received |
| Controllers | `ConfirmController` | PATCH /medication-requests/{id}/confirm |
| Controllers | `RejectController` | PATCH /medication-requests/{id}/reject |
| Requests | `StoreMedicationRequestRequest` | Validation for creating request |
| Resources | `MedicationRequestResource` | JSON transformation |
| Factory | `MedicationRequestFactory` | Test data generation |

### Mobile Components

| Type | Component | Purpose |
|------|-----------|---------|
| Types | `medicationRequest.ts` | TypeScript interfaces |
| Service | `medicationRequestService.ts` | API calls |
| Store | `medicationRequestStore.ts` | State management |
| Screen | `(auth)/receptor/requests.tsx` | Receptor's requests list |
| Screen | `(auth)/medication-requests/index.tsx` | Doctor's received requests |
| Screen | `(auth)/medication-requests/[id].tsx` | Request detail (doctor view) |
| Component | `RequestStatusBadge/` | Status badge component |
| Component | `MedicationRequestCard/` | Request card for lists |

---

## Backend Design

### Database Schema

#### New Table: `medication_requests`

```sql
CREATE TABLE medication_requests (
    id BIGSERIAL PRIMARY KEY,
    receptor_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    medication_offering_id BIGINT NOT NULL REFERENCES medication_offerings(id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Ensure only one pending request per offering
CREATE UNIQUE INDEX idx_medication_requests_active
ON medication_requests (medication_offering_id)
WHERE status = 'pending';

-- Index for receptor queries
CREATE INDEX idx_medication_requests_receptor
ON medication_requests (receptor_id);
```

#### Modified Table: `medication_offerings`

```sql
ALTER TABLE medication_offerings
ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'available';

-- Status values: 'available', 'reserved', 'completed'
```

### Model: MedicationRequest

```php
// app/Models/MedicationRequest.php
class MedicationRequest extends Model
{
    protected $fillable = [
        'receptor_id',
        'medication_offering_id',
        'status',
    ];

    // Relationships
    public function receptor(): BelongsTo // → User
    public function medicationOffering(): BelongsTo // → MedicationOffering

    // Scopes
    public function scopePending($query)
    public function scopeConfirmed($query)
    public function scopeRejected($query)
}
```

### Model Updates: MedicationOffering

```php
// Add to MedicationOffering model
protected $fillable = [..., 'status'];

// Add relationship
public function requests(): HasMany // → MedicationRequest
public function activeRequest(): HasOne // where status = pending

// Add scope
public function scopeAvailable($query)
public function scopeReserved($query)
```

### Model Updates: User

```php
// Add relationship
public function medicationRequests(): HasMany // → MedicationRequest (for receptors)
```

### Policy: MedicationRequestPolicy

```php
class MedicationRequestPolicy
{
    // Receptor can create request
    public function create(User $user): bool
    {
        return $user->role === 'receptor';
    }

    // Receptor can view own requests
    public function viewOwn(User $user): bool
    {
        return $user->role === 'receptor';
    }

    // Doctor can view received requests
    public function viewReceived(User $user): bool
    {
        return $user->role === 'doctor';
    }

    // Doctor can confirm/reject only their offering's requests
    public function confirm(User $user, MedicationRequest $request): bool
    {
        return $user->doctor?->id === $request->medicationOffering->doctor_id
            && $request->status === 'pending';
    }

    public function reject(User $user, MedicationRequest $request): bool
    {
        return $user->doctor?->id === $request->medicationOffering->doctor_id
            && $request->status === 'pending';
    }
}
```

### Actions

#### CreateMedicationRequestAction

```php
class CreateMedicationRequestAction
{
    public function execute(int $medicationOfferingId, User $receptor): MedicationRequest
    {
        // 1. Find offering (throws if not found)
        // 2. Validate offering is available
        // 3. Use DB transaction:
        //    - Create request with status 'pending'
        //    - Update offering status to 'reserved'
        // 4. Load relationships
        // 5. Return request
    }
}
```

#### ListReceptorRequestsAction

```php
class ListReceptorRequestsAction
{
    public function execute(User $receptor): Collection
    {
        return MedicationRequest::where('receptor_id', $receptor->id)
            ->with(['medicationOffering.drug', 'medicationOffering.doctor.user'])
            ->orderByDesc('created_at')
            ->get();
    }
}
```

#### ListDoctorRequestsAction

```php
class ListDoctorRequestsAction
{
    public function execute(User $doctor, ?string $status = null): Collection
    {
        $query = MedicationRequest::whereHas('medicationOffering', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->doctor->id);
        })
        ->with(['receptor', 'medicationOffering.drug']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->get();
    }
}
```

#### ConfirmMedicationRequestAction

```php
class ConfirmMedicationRequestAction
{
    public function execute(MedicationRequest $request): MedicationRequest
    {
        $request->update(['status' => 'confirmed']);
        // Offering remains reserved
        return $request->fresh(['medicationOffering.drug', 'receptor']);
    }
}
```

#### RejectMedicationRequestAction

```php
class RejectMedicationRequestAction
{
    public function execute(MedicationRequest $request): MedicationRequest
    {
        DB::transaction(function () use ($request) {
            $request->update(['status' => 'rejected']);
            $request->medicationOffering->update(['status' => 'available']);
        });

        return $request->fresh(['medicationOffering.drug', 'receptor']);
    }
}
```

### API Endpoints

| Method | Endpoint | Controller | Description |
|--------|----------|------------|-------------|
| POST | `/v1/medication-requests` | StoreController | Create request |
| GET | `/v1/medication-requests` | ListController | List receptor's requests |
| GET | `/v1/medication-requests/received` | ReceivedController | List doctor's received |
| PATCH | `/v1/medication-requests/{id}/confirm` | ConfirmController | Confirm request |
| PATCH | `/v1/medication-requests/{id}/reject` | RejectController | Reject request |

### Request Validation

#### StoreMedicationRequestRequest

```php
class StoreMedicationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MedicationRequest::class);
    }

    public function rules(): array
    {
        return [
            'medication_offering_id' => [
                'required',
                'integer',
                'exists:medication_offerings,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'medication_offering_id.required' => 'A oferta de medicamento é obrigatória.',
            'medication_offering_id.exists' => 'A oferta de medicamento não existe.',
        ];
    }
}
```

### API Resources

#### MedicationRequestResource

```php
class MedicationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'medication_offering' => MedicationOfferingSearchResource::make(
                $this->whenLoaded('medicationOffering')
            ),
            'receptor' => UserResource::make($this->whenLoaded('receptor')),
        ];
    }
}
```

### Route Registration

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    // ... existing routes

    Route::prefix('medication-requests')->middleware('auth:sanctum')->group(function () {
        Route::get('/', MedicationRequest\ListController::class)
            ->name('api.v1.medication-requests.list');
        Route::post('/', MedicationRequest\StoreController::class)
            ->name('api.v1.medication-requests.store');
        Route::get('/received', MedicationRequest\ReceivedController::class)
            ->name('api.v1.medication-requests.received');
        Route::patch('/{medicationRequest}/confirm', MedicationRequest\ConfirmController::class)
            ->name('api.v1.medication-requests.confirm');
        Route::patch('/{medicationRequest}/reject', MedicationRequest\RejectController::class)
            ->name('api.v1.medication-requests.reject');
    });
});
```

---

## Mobile Design

### TypeScript Types

```typescript
// types/medicationRequest.ts
export type MedicationRequestStatus = 'pending' | 'confirmed' | 'rejected';

export interface MedicationRequest {
  id: number;
  status: MedicationRequestStatus;
  created_at: string;
  updated_at: string;
  medication_offering?: MedicationOfferingSearchResult;
  receptor?: {
    id: number;
    name: string;
    email: string;
    phone_number?: string;
  };
}

export interface CreateMedicationRequestData {
  medication_offering_id: number;
}
```

### Service Layer

```typescript
// services/medicationRequestService.ts
export const medicationRequestService = {
  // Receptor creates request
  create: async (data: CreateMedicationRequestData): Promise<MedicationRequest>

  // Receptor lists their requests
  list: async (): Promise<MedicationRequest[]>

  // Doctor lists received requests
  listReceived: async (status?: MedicationRequestStatus): Promise<MedicationRequest[]>

  // Doctor confirms request
  confirm: async (id: number): Promise<MedicationRequest>

  // Doctor rejects request
  reject: async (id: number): Promise<MedicationRequest>
};
```

### State Management (Zustand)

```typescript
// stores/medicationRequestStore.ts
interface MedicationRequestState {
  requests: MedicationRequest[];           // Receptor's requests
  receivedRequests: MedicationRequest[];   // Doctor's received requests
  isLoading: boolean;
  error: string | null;

  // Receptor actions
  fetchMyRequests: () => Promise<void>;
  createRequest: (offeringId: number) => Promise<MedicationRequest>;

  // Doctor actions
  fetchReceivedRequests: (status?: string) => Promise<void>;
  confirmRequest: (id: number) => Promise<void>;
  rejectRequest: (id: number) => Promise<void>;

  clearError: () => void;
}
```

### Screen Structure

```
app/
├── (auth)/
│   ├── receptor/
│   │   ├── search.tsx          # Existing: search offerings
│   │   ├── offering/[id].tsx   # Modified: add request button
│   │   └── requests.tsx        # NEW: my requests list
│   ├── medication-requests/
│   │   ├── index.tsx           # NEW: doctor's received requests
│   │   └── [id].tsx            # NEW: request detail for doctor
│   └── dashboard.tsx           # Modified: add navigation for both roles
```

### UI Components

#### RequestStatusBadge

Small badge showing request status with appropriate colors:
- `pending` → Yellow/Orange background
- `confirmed` → Green background
- `rejected` → Red background

#### MedicationRequestCard

Card component for displaying request in lists:
- Drug name and substance
- Quantity and expiry
- Status badge
- Doctor/Receptor name (depending on context)
- Created date

### Navigation Updates

#### Dashboard (Modified)

Show different menus based on user role:
- **Doctor:** "Minhas Ofertas", "Nova Oferta", "Solicitações Recebidas"
- **Receptor:** "Buscar Medicamentos", "Minhas Solicitações"

### Offering Detail Screen (Modified)

Replace placeholder with functional "Solicitar Medicamento" button:
- Shows button only for available offerings
- Shows "Reservado" badge for reserved offerings
- Confirmation dialog before request
- Loading state during API call
- Success/error feedback

---

## Data Flow Diagrams

### Request Creation Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Receptor  │────▶│   Mobile    │────▶│   Backend   │────▶│  Database   │
│   (User)    │     │    App      │     │    API      │     │             │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
      │                   │                   │                   │
      │ Tap "Solicitar"   │                   │                   │
      │──────────────────▶│                   │                   │
      │                   │ POST /requests    │                   │
      │                   │──────────────────▶│                   │
      │                   │                   │ Validate          │
      │                   │                   │ Check available   │
      │                   │                   │──────────────────▶│
      │                   │                   │                   │ BEGIN TX
      │                   │                   │                   │ INSERT request
      │                   │                   │                   │ UPDATE offering
      │                   │                   │                   │ COMMIT
      │                   │                   │◀──────────────────│
      │                   │ 201 Created       │                   │
      │                   │◀──────────────────│                   │
      │ Success message   │                   │                   │
      │◀──────────────────│                   │                   │
```

### Doctor Confirmation Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Doctor    │────▶│   Mobile    │────▶│   Backend   │────▶│  Database   │
│   (User)    │     │    App      │     │    API      │     │             │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
      │                   │                   │                   │
      │ View received     │                   │                   │
      │──────────────────▶│                   │                   │
      │                   │ GET /received     │                   │
      │                   │──────────────────▶│                   │
      │                   │                   │ Query requests    │
      │                   │                   │──────────────────▶│
      │                   │                   │◀──────────────────│
      │                   │◀──────────────────│                   │
      │ Show list         │                   │                   │
      │◀──────────────────│                   │                   │
      │                   │                   │                   │
      │ Tap "Confirmar"   │                   │                   │
      │──────────────────▶│                   │                   │
      │                   │ PATCH /{id}/confirm                   │
      │                   │──────────────────▶│                   │
      │                   │                   │ Authorize         │
      │                   │                   │ Update status     │
      │                   │                   │──────────────────▶│
      │                   │                   │◀──────────────────│
      │                   │ 200 OK            │                   │
      │                   │◀──────────────────│                   │
      │ Success message   │                   │                   │
      │◀──────────────────│                   │                   │
```

---

## Implementation Order

### Backend (Execute in order)

1. **Database Layer**
   - [ ] Migration: add status to medication_offerings
   - [ ] Migration: create medication_requests table
   - [ ] Factory: MedicationRequestFactory

2. **Models**
   - [ ] Create MedicationRequest model
   - [ ] Update MedicationOffering model (status, relationships)
   - [ ] Update User model (medicationRequests relationship)

3. **Policy**
   - [ ] Create MedicationRequestPolicy
   - [ ] Register in AuthServiceProvider

4. **Actions**
   - [ ] CreateMedicationRequestAction
   - [ ] ListReceptorRequestsAction
   - [ ] ListDoctorRequestsAction
   - [ ] ConfirmMedicationRequestAction
   - [ ] RejectMedicationRequestAction

5. **Requests & Resources**
   - [ ] StoreMedicationRequestRequest
   - [ ] MedicationRequestResource
   - [ ] Update MedicationOfferingSearchResource (add status)

6. **Controllers**
   - [ ] StoreController
   - [ ] ListController
   - [ ] ReceivedController
   - [ ] ConfirmController
   - [ ] RejectController

7. **Routes**
   - [ ] Register all routes in api.php

8. **Tests**
   - [ ] Feature tests for all endpoints

### Mobile (Execute in order)

1. **Types & Service**
   - [ ] Create types/medicationRequest.ts
   - [ ] Create services/medicationRequestService.ts
   - [ ] Update types/medicationOffering.ts (add status)

2. **Store**
   - [ ] Create stores/medicationRequestStore.ts

3. **Components**
   - [ ] Create RequestStatusBadge component
   - [ ] Create MedicationRequestCard component

4. **Screens - Receptor**
   - [ ] Update offering/[id].tsx (add request button)
   - [ ] Create receptor/requests.tsx (my requests)

5. **Screens - Doctor**
   - [ ] Create medication-requests/index.tsx (received)
   - [ ] Create medication-requests/[id].tsx (detail)
   - [ ] Update dashboard.tsx (navigation for both roles)

6. **Navigation**
   - [ ] Update _layout files as needed

---

## Error Handling

### Backend Error Responses

| Scenario | HTTP Status | Message (PT-BR) |
|----------|-------------|-----------------|
| Offering not found | 404 | "Oferta de medicamento não encontrada." |
| Offering not available | 409 | "Esta oferta já foi reservada por outro usuário." |
| Not a receptor | 403 | "Apenas pacientes podem solicitar medicamentos." |
| Not offering owner | 403 | "Você não tem permissão para gerenciar esta solicitação." |
| Request not pending | 422 | "Esta solicitação já foi processada." |
| Validation error | 422 | Field-specific messages |

### Mobile Error Handling

- Show toast/alert with error message from API
- Refresh list after successful action
- Disable button during loading
- Handle network errors gracefully

---

## Security Considerations

1. **Authentication:** All endpoints require `auth:sanctum` middleware
2. **Authorization:** Policy checks at controller level
3. **Race conditions:** Database unique constraint prevents duplicate requests
4. **Data isolation:** Users can only see/manage their own data
5. **Input validation:** Form requests validate all input

---

## Technical Decisions

### D8: Partial Index for Active Requests

**Decision:** Use PostgreSQL partial unique index to ensure only one pending request per offering.

**Rationale:**
- Prevents race conditions at database level
- More reliable than application-level checks
- Allows historical rejected requests to coexist

**SQLite Fallback:** For testing, use application-level validation with DB transaction.

### D9: Status on MedicationOffering

**Decision:** Add explicit `status` field to MedicationOffering rather than deriving from requests.

**Rationale:**
- Simpler queries for available offerings
- Clear state management
- Easier to extend with future statuses (e.g., 'completed')

### D10: Receptor via User Model

**Decision:** Store `receptor_id` pointing to `users.id` rather than creating a separate `Receptor` model.

**Rationale:**
- Simpler architecture (users already have `role` field)
- Consistent with existing pattern (doctors have separate table for CRM data)
- Receptors don't have additional required fields beyond User

---

## File Summary

### New Files (Backend)

```
web/
├── app/
│   ├── Actions/
│   │   └── MedicationRequest/
│   │       ├── CreateMedicationRequestAction.php
│   │       ├── ListReceptorRequestsAction.php
│   │       ├── ListDoctorRequestsAction.php
│   │       ├── ConfirmMedicationRequestAction.php
│   │       └── RejectMedicationRequestAction.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/MedicationRequest/
│   │   │   ├── StoreController.php
│   │   │   ├── ListController.php
│   │   │   ├── ReceivedController.php
│   │   │   ├── ConfirmController.php
│   │   │   └── RejectController.php
│   │   ├── Requests/Api/V1/MedicationRequest/
│   │   │   └── StoreMedicationRequestRequest.php
│   │   └── Resources/Api/V1/
│   │       └── MedicationRequestResource.php
│   ├── Models/
│   │   └── MedicationRequest.php
│   └── Policies/
│       └── MedicationRequestPolicy.php
├── database/
│   ├── factories/
│   │   └── MedicationRequestFactory.php
│   └── migrations/
│       ├── XXXX_XX_XX_add_status_to_medication_offerings_table.php
│       └── XXXX_XX_XX_create_medication_requests_table.php
└── tests/Feature/Api/V1/MedicationRequest/
    ├── StoreTest.php
    ├── ListTest.php
    ├── ReceivedTest.php
    ├── ConfirmTest.php
    └── RejectTest.php
```

### New Files (Mobile)

```
mobile/
├── types/
│   └── medicationRequest.ts
├── services/
│   └── medicationRequestService.ts
├── stores/
│   └── medicationRequestStore.ts
├── components/
│   ├── RequestStatusBadge/
│   │   └── index.tsx
│   └── MedicationRequestCard/
│       └── index.tsx
└── app/(auth)/
    ├── receptor/
    │   └── requests.tsx
    └── medication-requests/
        ├── index.tsx
        └── [id].tsx
```

### Modified Files

```
web/
├── app/Models/MedicationOffering.php      # Add status, relationships
├── app/Models/User.php                     # Add medicationRequests relationship
├── app/Http/Resources/Api/V1/
│   └── MedicationOfferingSearchResource.php  # Add status field
├── app/Providers/AuthServiceProvider.php   # Register policy
└── routes/api.php                          # Add routes

mobile/
├── types/medicationOffering.ts             # Add status field
├── services/medicationOfferingService.ts   # Update return types
├── app/(auth)/receptor/offering/[id].tsx   # Add request button
└── app/(auth)/dashboard.tsx                # Add role-based navigation
```

---

## Phase 2 Sign-off

**Status:** Complete
**Next Phase:** Test Planning (Phase 3)
**Dependencies Identified:** None blocking
**Risk Assessment:** Low - follows established patterns
