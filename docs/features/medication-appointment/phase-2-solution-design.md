# Phase 2: Solution Design - Medication Appointment System

## Overview

This document defines the technical architecture and implementation strategy for the Medication Appointment System feature, including bug fixes for navigation redirects.

---

## Architecture Overview

### System Components

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              MOBILE APP                                  │
│  ┌─────────────────┐  ┌──────────────────┐  ┌────────────────────────┐ │
│  │ Appointment     │  │ Types            │  │ Stores                 │ │
│  │ Screens         │  │ medicationAppt.ts│  │ medicationApptStore.ts │ │
│  │ - create        │  └──────────────────┘  └────────────────────────┘ │
│  │ - list          │           │                       │                │
│  │ - detail        │           ▼                       ▼                │
│  └─────────────────┘  ┌──────────────────────────────────────────────┐ │
│           │           │ Services: medicationAppointmentService.ts    │ │
│           ▼           └──────────────────────────────────────────────┘ │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                         API Client (axios)                        │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼ HTTP
┌─────────────────────────────────────────────────────────────────────────┐
│                            LARAVEL BACKEND                               │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                         Routes (api.php)                          │  │
│  │  /v1/medication-appointments/*                                    │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│           │                                                              │
│           ▼                                                              │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ Controllers (MedicationAppointment/)                              │  │
│  │  - StoreController                                                │  │
│  │  - ListController (receptor)                                      │  │
│  │  - ReceivedController (doctor)                                    │  │
│  │  - ConfirmDeliveryReceptorController                              │  │
│  │  - ConfirmDeliveryDoctorController                                │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│           │                                                              │
│           ▼                                                              │
│  ┌────────────────────┐  ┌─────────────────┐  ┌─────────────────────┐  │
│  │ FormRequests       │  │ Actions         │  │ Policies            │  │
│  │ - Store            │  │ - Create        │  │ MedicationAppt      │  │
│  │ - ConfirmDelivery  │  │ - ConfirmDel*   │  │ Policy              │  │
│  └────────────────────┘  └─────────────────┘  └─────────────────────┘  │
│           │                       │                                      │
│           ▼                       ▼                                      │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ Models: MedicationAppointment, MedicationRequest, MedicationOff* │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│           │                                                              │
│           ▼                                                              │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                         PostgreSQL Database                        │  │
│  │  medication_appointments table                                     │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Database Design

### New Table: medication_appointments

```sql
CREATE TABLE medication_appointments (
    id BIGSERIAL PRIMARY KEY,
    medication_request_id BIGINT NOT NULL UNIQUE,
    address_id BIGINT NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    status VARCHAR(20) DEFAULT 'scheduled' NOT NULL,
    receptor_confirmed BOOLEAN DEFAULT FALSE NOT NULL,
    doctor_confirmed BOOLEAN DEFAULT FALSE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_medication_request
        FOREIGN KEY (medication_request_id)
        REFERENCES medication_requests(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_address
        FOREIGN KEY (address_id)
        REFERENCES addresses(id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_medication_appointments_scheduled_date
    ON medication_appointments(scheduled_date);

CREATE INDEX idx_medication_appointments_status_date
    ON medication_appointments(status, scheduled_date);
```

### Entity Relationships

```
User (receptor)
  └── has many → MedicationRequest
                   └── has one → MedicationAppointment
                                   └── belongs to → Address

User (doctor)
  └── has one → Doctor
                  └── has many → MedicationOffering
                                   └── has many → MedicationRequest
                                                   └── has one → MedicationAppointment

Address
  └── belongs to → User (doctor)
  └── has many → MedicationAppointment
```

---

## API Design

### Endpoints Summary

| Method | Endpoint | Description | Actor |
|--------|----------|-------------|-------|
| POST | `/v1/medication-appointments` | Create appointment | Receptor |
| GET | `/v1/medication-appointments` | List receptor's appointments | Receptor |
| GET | `/v1/medication-appointments/received` | List doctor's appointments | Doctor |
| PATCH | `/v1/medication-appointments/{id}/confirm-delivery-receptor` | Receptor confirms delivery | Receptor |
| PATCH | `/v1/medication-appointments/{id}/confirm-delivery-doctor` | Doctor confirms delivery | Doctor |

### API Contracts

#### POST /v1/medication-appointments

**Request:**
```json
{
  "medication_request_id": 123,
  "scheduled_date": "2026-01-15",
  "scheduled_time": "14:30"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 1,
    "scheduled_date": "2026-01-15",
    "scheduled_time": "14:30",
    "status": "scheduled",
    "receptor_confirmed": false,
    "doctor_confirmed": false,
    "created_at": "2026-01-11T10:30:00Z",
    "updated_at": "2026-01-11T10:30:00Z",
    "medication_request": {
      "id": 123,
      "status": "confirmed",
      "medication_offering": {
        "id": 45,
        "quantity": 10,
        "lot_number": "ABC123",
        "expires_at": "2026-06-15",
        "status": "reserved",
        "drug": { "id": 1, "product_name": "Paracetamol", ... },
        "doctor": { "id": 1, "name": "Dr. João Silva" }
      }
    },
    "address": {
      "id": 5,
      "location_name": "Consultório Centro",
      "full_address": "Rua das Flores, 123 - Centro",
      "complement": "Sala 101",
      "cep": "01234567"
    }
  }
}
```

**Validation Errors (422):**
```json
{
  "message": "A data deve ser hoje ou no futuro.",
  "errors": {
    "scheduled_date": ["A data deve ser hoje ou no futuro."]
  }
}
```

#### GET /v1/medication-appointments

**Query Parameters:**
- `status` (optional): Filter by status ('scheduled', 'completed')

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "scheduled_date": "2026-01-15",
      "scheduled_time": "14:30",
      "status": "scheduled",
      "receptor_confirmed": false,
      "doctor_confirmed": false,
      "created_at": "2026-01-11T10:30:00Z",
      "updated_at": "2026-01-11T10:30:00Z",
      "medication_request": { ... },
      "address": { ... }
    }
  ]
}
```

#### PATCH /v1/medication-appointments/{id}/confirm-delivery-receptor

**Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "receptor_confirmed": true,
    "doctor_confirmed": false,
    "status": "scheduled",
    ...
  }
}
```

**After both confirm:**
```json
{
  "data": {
    "id": 1,
    "receptor_confirmed": true,
    "doctor_confirmed": true,
    "status": "completed",
    ...
  }
}
```

---

## Backend File Structure

### Files to Create

```
web/
├── app/
│   ├── Actions/
│   │   └── MedicationAppointment/
│   │       ├── CreateMedicationAppointmentAction.php
│   │       ├── ListReceptorAppointmentsAction.php
│   │       ├── ListDoctorAppointmentsAction.php
│   │       ├── ConfirmDeliveryReceptorAction.php
│   │       └── ConfirmDeliveryDoctorAction.php
│   │
│   ├── Http/
│   │   ├── Controllers/Api/V1/MedicationAppointment/
│   │   │   ├── StoreController.php
│   │   │   ├── ListController.php
│   │   │   ├── ReceivedController.php
│   │   │   ├── ConfirmDeliveryReceptorController.php
│   │   │   └── ConfirmDeliveryDoctorController.php
│   │   │
│   │   ├── Requests/Api/V1/MedicationAppointment/
│   │   │   └── StoreMedicationAppointmentRequest.php
│   │   │
│   │   └── Resources/Api/V1/
│   │       └── MedicationAppointmentResource.php
│   │
│   ├── Models/
│   │   └── MedicationAppointment.php
│   │
│   └── Policies/
│       └── MedicationAppointmentPolicy.php
│
├── database/
│   ├── factories/
│   │   └── MedicationAppointmentFactory.php
│   │
│   └── migrations/
│       └── 2026_01_11_XXXXXX_create_medication_appointments_table.php
│
└── tests/Feature/Api/V1/MedicationAppointment/
    ├── StoreTest.php
    ├── ListTest.php
    ├── ReceivedTest.php
    ├── ConfirmDeliveryReceptorTest.php
    └── ConfirmDeliveryDoctorTest.php
```

### Files to Modify

```
web/
├── app/Models/
│   ├── MedicationRequest.php  # Add hasOne appointment relationship
│   └── MedicationOffering.php # Add 'completed' status scope
│
├── app/Http/Resources/Api/V1/
│   └── MedicationRequestResource.php # Add appointment relationship
│
└── routes/
    └── api.php # Add medication-appointments routes
```

---

## Backend Implementation Details

### Model: MedicationAppointment

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationAppointment extends Model
{
    use HasFactory;

    protected $table = 'medication_appointments';

    protected $fillable = [
        'medication_request_id',
        'address_id',
        'scheduled_date',
        'scheduled_time',
        'status',
        'receptor_confirmed',
        'doctor_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date:Y-m-d',
            'scheduled_time' => 'string',
            'receptor_confirmed' => 'boolean',
            'doctor_confirmed' => 'boolean',
        ];
    }

    public function medicationRequest(): BelongsTo
    {
        return $this->belongsTo(MedicationRequest::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_date', '>=', now()->toDateString())
                     ->orderBy('scheduled_date')
                     ->orderBy('scheduled_time');
    }
}
```

### Policy: MedicationAppointmentPolicy

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MedicationAppointment;
use App\Models\MedicationRequest;
use App\Models\User;

class MedicationAppointmentPolicy
{
    /**
     * Receptor can create appointment for their confirmed request.
     */
    public function create(User $user, MedicationRequest $request): bool
    {
        return $user->role === 'receptor'
            && $request->receptor_id === $user->id
            && $request->status === 'confirmed';
    }

    /**
     * Receptor can view their own appointments.
     */
    public function viewOwn(User $user): bool
    {
        return $user->role === 'receptor';
    }

    /**
     * Doctor can view appointments for their offerings.
     */
    public function viewReceived(User $user): bool
    {
        return $user->role === 'doctor' && $user->doctor !== null;
    }

    /**
     * Receptor can confirm delivery for their appointment.
     */
    public function confirmDeliveryReceptor(User $user, MedicationAppointment $appointment): bool
    {
        return $user->role === 'receptor'
            && $appointment->medicationRequest->receptor_id === $user->id
            && $appointment->status === 'scheduled';
    }

    /**
     * Doctor can confirm delivery for their appointment.
     */
    public function confirmDeliveryDoctor(User $user, MedicationAppointment $appointment): bool
    {
        if ($user->doctor === null) {
            return false;
        }

        return $appointment->medicationRequest->medicationOffering->doctor_id === $user->doctor->id
            && $appointment->status === 'scheduled';
    }
}
```

### Action: CreateMedicationAppointmentAction

```php
<?php

declare(strict_types=1);

namespace App\Actions\MedicationAppointment;

use App\Models\MedicationAppointment;
use App\Models\MedicationRequest;

class CreateMedicationAppointmentAction
{
    public function execute(
        MedicationRequest $request,
        string $scheduledDate,
        string $scheduledTime,
        int $addressId
    ): MedicationAppointment {
        $appointment = MedicationAppointment::create([
            'medication_request_id' => $request->id,
            'address_id' => $addressId,
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'status' => 'scheduled',
        ]);

        return $appointment->load([
            'medicationRequest.medicationOffering.drug',
            'medicationRequest.medicationOffering.doctor.user',
            'address',
        ]);
    }
}
```

### Action: ConfirmDeliveryDoctorAction

```php
<?php

declare(strict_types=1);

namespace App\Actions\MedicationAppointment;

use App\Models\MedicationAppointment;
use Illuminate\Support\Facades\DB;

class ConfirmDeliveryDoctorAction
{
    public function execute(MedicationAppointment $appointment): MedicationAppointment
    {
        return DB::transaction(function () use ($appointment) {
            $appointment->update(['doctor_confirmed' => true]);

            // If both parties confirmed, complete the appointment and offering
            if ($appointment->receptor_confirmed) {
                $appointment->update(['status' => 'completed']);
                $appointment->medicationRequest->medicationOffering->update([
                    'status' => 'completed'
                ]);
            }

            return $appointment->fresh([
                'medicationRequest.medicationOffering.drug',
                'medicationRequest.medicationOffering.doctor.user',
                'medicationRequest.receptor',
                'address',
            ]);
        });
    }
}
```

### Routes Addition (api.php)

```php
Route::prefix('medication-appointments')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/', MedicationAppointment\ListController::class)
        ->name('api.v1.medication-appointments.list');
    Route::post('/', MedicationAppointment\StoreController::class)
        ->name('api.v1.medication-appointments.store');
    Route::get('/received', MedicationAppointment\ReceivedController::class)
        ->name('api.v1.medication-appointments.received');
    Route::patch('/{medicationAppointment}/confirm-delivery-receptor',
        MedicationAppointment\ConfirmDeliveryReceptorController::class)
        ->name('api.v1.medication-appointments.confirm-delivery-receptor');
    Route::patch('/{medicationAppointment}/confirm-delivery-doctor',
        MedicationAppointment\ConfirmDeliveryDoctorController::class)
        ->name('api.v1.medication-appointments.confirm-delivery-doctor');
});
```

---

## Mobile Frontend Design

### Files to Create

```
mobile/
├── types/
│   └── medicationAppointment.ts
│
├── services/
│   └── medicationAppointmentService.ts
│
├── stores/
│   └── medicationAppointmentStore.ts
│
├── components/
│   ├── MedicationAppointmentCard/
│   │   ├── index.tsx
│   │   └── styles.ts
│   │
│   ├── AppointmentStatusBadge/
│   │   └── index.tsx
│   │
│   └── DateTimePicker/
│       └── index.tsx
│
└── app/(auth)/
    ├── receptor/
    │   └── appointments/
    │       ├── index.tsx         # List receptor's appointments
    │       └── create/[requestId].tsx  # Create appointment for request
    │
    └── appointments/
        └── index.tsx             # List doctor's appointments
```

### Files to Modify

```
mobile/
├── app/login.tsx                 # Verify redirect logic (appears correct)
├── screens/ReceptorRegistration/index.tsx  # Fix redirect to receptor/search
└── app/(auth)/receptor/
    ├── requests.tsx              # Add "Schedule" button for confirmed requests
    └── _layout.tsx               # Add appointments tab
```

### TypeScript Types

```typescript
// types/medicationAppointment.ts

import { MedicationRequest } from './medicationRequest';

export type MedicationAppointmentStatus = 'scheduled' | 'completed';

export interface Address {
  id: number;
  location_name: string;
  full_address: string;
  complement?: string;
  cep: string;
}

export interface MedicationAppointment {
  id: number;
  scheduled_date: string;
  scheduled_time: string;
  status: MedicationAppointmentStatus;
  receptor_confirmed: boolean;
  doctor_confirmed: boolean;
  created_at: string;
  updated_at: string;
  medication_request?: MedicationRequest;
  address?: Address;
}

export interface CreateMedicationAppointmentData {
  medication_request_id: number;
  scheduled_date: string;
  scheduled_time: string;
}

export const APPOINTMENT_STATUS_LABELS: Record<MedicationAppointmentStatus, string> = {
  scheduled: 'Agendado',
  completed: 'Concluído',
};

export const APPOINTMENT_STATUS_COLORS: Record<MedicationAppointmentStatus, string> = {
  scheduled: '#3b82f6', // blue
  completed: '#10b981', // green
};
```

### Service Layer

```typescript
// services/medicationAppointmentService.ts

import api from './api';
import {
  MedicationAppointment,
  CreateMedicationAppointmentData,
  MedicationAppointmentStatus,
} from '@/types/medicationAppointment';
import { extractErrorMessage } from '@/utils/validation/errorHelpers';

export const medicationAppointmentService = {
  create: async (data: CreateMedicationAppointmentData): Promise<MedicationAppointment> => {
    try {
      const response = await api.post('/v1/medication-appointments', data);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  list: async (status?: MedicationAppointmentStatus): Promise<MedicationAppointment[]> => {
    try {
      const params = status ? { status } : {};
      const response = await api.get('/v1/medication-appointments', { params });
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  listReceived: async (status?: MedicationAppointmentStatus): Promise<MedicationAppointment[]> => {
    try {
      const params = status ? { status } : {};
      const response = await api.get('/v1/medication-appointments/received', { params });
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  confirmDeliveryReceptor: async (id: number): Promise<MedicationAppointment> => {
    try {
      const response = await api.patch(`/v1/medication-appointments/${id}/confirm-delivery-receptor`);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  confirmDeliveryDoctor: async (id: number): Promise<MedicationAppointment> => {
    try {
      const response = await api.patch(`/v1/medication-appointments/${id}/confirm-delivery-doctor`);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },
};
```

### Zustand Store

```typescript
// stores/medicationAppointmentStore.ts

import { create } from 'zustand';
import { medicationAppointmentService } from '@/services/medicationAppointmentService';
import {
  MedicationAppointment,
  MedicationAppointmentStatus,
  CreateMedicationAppointmentData
} from '@/types/medicationAppointment';

interface MedicationAppointmentStoreState {
  appointments: MedicationAppointment[];
  receivedAppointments: MedicationAppointment[];
  isLoading: boolean;
  error: string | null;

  // Receptor actions
  fetchMyAppointments: (status?: MedicationAppointmentStatus) => Promise<void>;
  createAppointment: (data: CreateMedicationAppointmentData) => Promise<MedicationAppointment>;
  confirmDeliveryReceptor: (id: number) => Promise<void>;

  // Doctor actions
  fetchReceivedAppointments: (status?: MedicationAppointmentStatus) => Promise<void>;
  confirmDeliveryDoctor: (id: number) => Promise<void>;

  clearError: () => void;
}

export const useMedicationAppointmentStore = create<MedicationAppointmentStoreState>((set, get) => ({
  appointments: [],
  receivedAppointments: [],
  isLoading: false,
  error: null,

  fetchMyAppointments: async (status) => {
    set({ isLoading: true, error: null });
    try {
      const appointments = await medicationAppointmentService.list(status);
      set({ appointments, isLoading: false });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  createAppointment: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const appointment = await medicationAppointmentService.create(data);
      set((state) => ({
        appointments: [appointment, ...state.appointments],
        isLoading: false,
      }));
      return appointment;
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  confirmDeliveryReceptor: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const updated = await medicationAppointmentService.confirmDeliveryReceptor(id);
      set((state) => ({
        appointments: state.appointments.map((a) => (a.id === id ? updated : a)),
        isLoading: false,
      }));
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  fetchReceivedAppointments: async (status) => {
    set({ isLoading: true, error: null });
    try {
      const receivedAppointments = await medicationAppointmentService.listReceived(status);
      set({ receivedAppointments, isLoading: false });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  confirmDeliveryDoctor: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const updated = await medicationAppointmentService.confirmDeliveryDoctor(id);
      set((state) => ({
        receivedAppointments: state.receivedAppointments.map((a) => (a.id === id ? updated : a)),
        isLoading: false,
      }));
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  clearError: () => set({ error: null }),
}));
```

---

## Bug Fixes Design

### Bug 1: Login Redirect (Verify)

**File:** `mobile/app/login.tsx` (lines 86-93)

**Current Code:**
```typescript
if (user.role === 'doctor') {
  router.replace('/(auth)/dashboard' as Href);
} else if (user.role === 'receptor') {
  router.replace('/(auth)/receptor/search' as Href);
} else {
  router.replace('/(auth)/dashboard' as Href);
}
```

**Analysis:** The code appears correct. Need to verify if the issue is:
1. User role not being returned correctly from backend
2. Session storage issue
3. Auth middleware redirect issue

**Action:** Verify during implementation, add console.log to debug if needed.

### Bug 2: Receptor Registration Redirect

**File:** `mobile/screens/ReceptorRegistration/index.tsx` (line 125)

**Current Code:**
```typescript
if (success) {
  router.replace('/(auth)/dashboard');
}
```

**Fix:**
```typescript
if (success) {
  router.replace('/(auth)/receptor/search');
}
```

---

## Implementation Order

### Phase 1: Database & Model (Backend Foundation)

1. Create migration for `medication_appointments` table
2. Create `MedicationAppointment` model
3. Add `appointment()` relationship to `MedicationRequest` model
4. Add `completed` scope to `MedicationOffering` model
5. Create factory for testing

### Phase 2: Business Logic (Actions & Policies)

1. Create `MedicationAppointmentPolicy`
2. Register policy in `AuthServiceProvider`
3. Create `CreateMedicationAppointmentAction`
4. Create `ListReceptorAppointmentsAction`
5. Create `ListDoctorAppointmentsAction`
6. Create `ConfirmDeliveryReceptorAction`
7. Create `ConfirmDeliveryDoctorAction`

### Phase 3: API Layer (Controllers & Routes)

1. Create `StoreMedicationAppointmentRequest` (validation)
2. Create `MedicationAppointmentResource`
3. Create all 5 controllers
4. Add routes to `api.php`
5. Update `MedicationRequestResource` to include appointment

### Phase 4: Tests (Backend)

1. Create test files for all endpoints
2. Run `composer fix` to ensure code quality
3. Run `composer t` to verify all tests pass

### Phase 5: Bug Fixes (Mobile)

1. Fix receptor registration redirect
2. Verify login redirect (debug if needed)

### Phase 6: Mobile Frontend

1. Create TypeScript types
2. Create API service
3. Create Zustand store
4. Create `AppointmentStatusBadge` component
5. Create `MedicationAppointmentCard` component
6. Create appointment list screen (receptor)
7. Create appointment creation screen (receptor)
8. Create appointment list screen (doctor)
9. Add "Schedule" action to confirmed requests
10. Update navigation layouts

---

## Technical Decisions

### D1: Address Selection Strategy

**Decision:** Use doctor's first registered address automatically.

**Rationale:**
- Simplifies MVP implementation
- Doctor already has at least one address (registration requirement)
- `address_id` is stored explicitly for future flexibility

**Future Enhancement:**
- Allow receptor to choose from doctor's addresses
- Add `preferred_address_id` to `MedicationOffering` model

### D2: Time Storage Format

**Decision:** Store time as string (HH:mm format) in database.

**Rationale:**
- PostgreSQL TIME type works well
- Simpler serialization/deserialization
- No timezone complications for MVP

### D3: Confirmation Before Scheduled Time

**Decision:** Allow confirmation only on or after scheduled date (not time).

**Rationale:**
- Checking exact time is fragile (timezone issues)
- Checking date is simpler and sufficient for MVP
- Allows flexibility if appointment happens earlier same day

### D4: Status Transitions

**Decision:** Only `scheduled → completed` transition implemented.

**Rationale:**
- MVP scope doesn't include cancellation/rescheduling
- Future: Add 'cancelled', 'rescheduled' statuses
- Simpler state machine reduces bugs

---

## Validation Rules Summary

### StoreMedicationAppointmentRequest

| Field | Rules | Message (PT-BR) |
|-------|-------|-----------------|
| medication_request_id | required, exists:medication_requests,id | A solicitação de medicamento é obrigatória. / A solicitação não existe. |
| scheduled_date | required, date, after_or_equal:today | A data é obrigatória. / A data deve ser hoje ou no futuro. |
| scheduled_time | required, date_format:H:i | O horário é obrigatório. / Formato de horário inválido. |

### Additional Business Validations (in Action/Policy)

| Rule | Error Message |
|------|---------------|
| Request must be confirmed | Apenas solicitações confirmadas podem ter agendamento. |
| No existing appointment | Já existe um agendamento para esta solicitação. |
| User must be receptor | Apenas pacientes podem criar agendamentos. |
| Address must belong to doctor | Endereço inválido. |

---

## Security Considerations

1. **Authentication:** All endpoints require Sanctum token
2. **Authorization:** Policies enforce role-based access
3. **Data Isolation:** Users can only see their own appointments
4. **Input Validation:** All inputs validated and sanitized
5. **SQL Injection:** Using Eloquent ORM (parameterized queries)
6. **CSRF:** N/A for API (token-based auth)

---

## Sign-off

**Phase 2 Status:** ✅ Complete

**Next Phase:** Test Planning (Phase 3)

**Architecture Summary:**
- 1 new database table
- 5 new API endpoints
- 5 controllers, 5 actions, 1 policy, 1 request, 1 resource
- Full mobile frontend (types, service, store, components, screens)
- 2 bug fixes

**Reviewed By:** Awaiting user confirmation
