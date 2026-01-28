# Architecture

Technical architecture and patterns used in DoaFarma.

---

## Repository Structure

```
doafarma/
├── web/                          # Laravel 12 backend
│   ├── app/
│   │   ├── Actions/              # Business logic (single-purpose classes)
│   │   │   ├── Auth/
│   │   │   ├── Drug/
│   │   │   ├── MedicationOffering/
│   │   │   ├── MedicationRequest/
│   │   │   ├── MedicationAppointment/
│   │   │   ├── DoctorRating/
│   │   │   └── PushToken/
│   │   ├── Enums/                # PHP enums
│   │   ├── Filament/             # Admin panel
│   │   │   ├── Resources/        # CRUD resources
│   │   │   ├── Widgets/          # Dashboard widgets
│   │   │   └── Pages/            # Custom pages
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/V1/       # Versioned API controllers
│   │   │   ├── Middleware/
│   │   │   ├── Requests/         # Form validation
│   │   │   └── Resources/        # JSON transformation
│   │   ├── Models/               # Eloquent models
│   │   ├── Notifications/        # Push notification classes
│   │   ├── Policies/             # Authorization
│   │   └── Rules/                # Custom validation rules
│   ├── config/                   # Configuration files
│   ├── database/
│   │   ├── factories/            # Model factories
│   │   ├── migrations/           # Schema definitions
│   │   └── seeders/              # Initial data
│   ├── routes/
│   │   ├── api.php              # API routes
│   │   └── web.php              # Web routes (admin)
│   └── tests/
│       ├── Feature/              # Integration tests
│       └── Unit/                 # Unit tests
│
├── mobile/                       # React Native (Expo)
│   ├── app/                      # File-based routing (Expo Router)
│   │   ├── (auth)/              # Protected routes
│   │   │   ├── doctor/          # Doctor-specific screens
│   │   │   ├── receptor/        # Receptor-specific screens
│   │   │   ├── medication-offerings/
│   │   │   ├── medication-requests/
│   │   │   └── medication-appointments/
│   │   └── register/            # Registration screens
│   ├── components/              # Reusable UI components
│   ├── services/                # API service layer
│   ├── stores/                  # Zustand state management
│   ├── types/                   # TypeScript definitions
│   └── utils/                   # Helper functions
│
└── docs/                        # Documentation
    ├── CONTEXT.md               # Business domain
    ├── ARCHITECTURE.md          # This file
    ├── BUSINESS_FLOWS.md        # User flows
    ├── API_REFERENCE.md         # API documentation
    ├── openapi.yaml             # OpenAPI specification
    ├── DEVELOPMENT_GUIDE.md     # Setup guide
    ├── CONVENTIONS.md           # Code standards
    └── DECISIONS.md             # Architectural decisions
```

---

## Backend Patterns

### Actions (Service Layer)

Business logic lives in `app/Actions/`. Controllers only handle HTTP concerns.

```php
// app/Actions/MedicationOffering/StoreMedicationOfferingAction.php
class StoreMedicationOfferingAction
{
    public function execute(Doctor $doctor, array $data): MedicationOffering
    {
        return $doctor->offerings()->create([
            'drug_id' => $data['drug_id'],
            'lot_number' => $data['lot_number'],
            'expires_at' => $data['expires_at'],
            'quantity' => $data['quantity'],
            'status' => 'available',
        ]);
    }
}

// Controller delegates to action
class StoreController extends Controller
{
    public function __invoke(
        StoreMedicationOfferingRequest $request,
        StoreMedicationOfferingAction $action
    ) {
        $offering = $action->execute(
            $request->user()->doctor,
            $request->validated()
        );

        return new MedicationOfferingResource($offering);
    }
}
```

**Benefits:**
- Single Responsibility Principle
- Easy to test in isolation
- Reusable across controllers, jobs, commands

### Form Requests

Validation and authorization in request classes.

```php
// app/Http/Requests/MedicationOffering/StoreMedicationOfferingRequest.php
class StoreMedicationOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::DOCTOR;
    }

    public function rules(): array
    {
        return [
            'drug_id' => ['required', 'exists:drugs,id'],
            'lot_number' => ['required', 'string', 'max:50'],
            'expires_at' => ['required', 'date', 'after:today', 'before:+10 years'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'drug_id.required' => 'O medicamento é obrigatório.',
            'expires_at.after' => 'A data de validade deve ser futura.',
        ];
    }
}
```

### Resources (JSON Transformation)

```php
// app/Http/Resources/Api/V1/MedicationOfferingResource.php
class MedicationOfferingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'lot_number' => $this->lot_number,
            'expires_at' => $this->expires_at->format('Y-m-d'),
            'quantity' => $this->quantity,
            'status' => $this->status,
            'drug' => new DrugResource($this->whenLoaded('drug')),
            'doctor' => new DoctorResource($this->whenLoaded('doctor')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

### Policies (Authorization)

```php
// app/Policies/MedicationOfferingPolicy.php
class MedicationOfferingPolicy
{
    public function update(User $user, MedicationOffering $offering): bool
    {
        return $user->doctor?->id === $offering->doctor_id;
    }

    public function delete(User $user, MedicationOffering $offering): bool
    {
        return $user->doctor?->id === $offering->doctor_id
            && $offering->status === 'available';
    }
}
```

### Enums

```php
// app/Enums/UserRole.php
enum UserRole: string
{
    case DOCTOR = 'doctor';
    case RECEPTOR = 'receptor';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::DOCTOR => 'Médico',
            self::RECEPTOR => 'Receptor',
            self::ADMIN => 'Administrador',
        };
    }
}
```

---

## Mobile Patterns

### File-based Routing (Expo Router)

```
app/
├── _layout.tsx                   # Root layout with auth check
├── index.tsx                     # Landing/splash
├── login.tsx                     # Login screen
├── register/
│   ├── index.tsx                # Registration type selection
│   ├── doctor.tsx               # Doctor registration
│   └── receptor.tsx             # Receptor registration
└── (auth)/                      # Protected group (requires login)
    ├── _layout.tsx              # Auth layout with navigation
    ├── dashboard.tsx            # Home screen
    ├── receptor/
    │   └── (tabs)/              # Tab navigation for receptor
    │       ├── search.tsx       # Search offerings
    │       ├── requests.tsx     # My requests
    │       └── appointments.tsx # My appointments
    └── doctor/
        ├── offerings/           # Doctor's offerings
        └── history.tsx          # Donation history
```

### Services (API Layer)

```typescript
// services/medicationOfferingService.ts
import api from './api';
import type { MedicationOffering, CreateOfferingData } from '@/types';

export async function getOfferings(): Promise<MedicationOffering[]> {
    const response = await api.get('/v1/medication-offerings');
    return response.data.data;
}

export async function createOffering(data: CreateOfferingData): Promise<MedicationOffering> {
    const response = await api.post('/v1/medication-offerings', data);
    return response.data.data;
}

export async function searchOfferings(query: string): Promise<MedicationOffering[]> {
    const response = await api.get('/v1/medication-offerings/search', {
        params: { q: query }
    });
    return response.data.data;
}
```

### Stores (Zustand)

```typescript
// stores/medicationOfferingStore.ts
import { create } from 'zustand';
import * as offeringService from '@/services/medicationOfferingService';
import type { MedicationOffering } from '@/types';

interface OfferingState {
    offerings: MedicationOffering[];
    isLoading: boolean;
    error: string | null;
    fetchOfferings: () => Promise<void>;
    createOffering: (data: CreateOfferingData) => Promise<MedicationOffering>;
}

export const useOfferingStore = create<OfferingState>((set, get) => ({
    offerings: [],
    isLoading: false,
    error: null,

    fetchOfferings: async () => {
        set({ isLoading: true, error: null });
        try {
            const offerings = await offeringService.getOfferings();
            set({ offerings, isLoading: false });
        } catch (error) {
            set({ error: error.message, isLoading: false });
        }
    },

    createOffering: async (data) => {
        const offering = await offeringService.createOffering(data);
        set({ offerings: [...get().offerings, offering] });
        return offering;
    },
}));
```

### Types

```typescript
// types/medicationOffering.ts
export interface Drug {
    id: number;
    product_name: string;
    substance: string;
    laboratory: string;
}

export interface MedicationOffering {
    id: number;
    lot_number: string;
    expires_at: string;
    quantity: number;
    status: 'available' | 'reserved' | 'completed';
    drug?: Drug;
    doctor?: Doctor;
    created_at: string;
}

export interface CreateOfferingData {
    drug_id: number;
    lot_number: string;
    expires_at: string;
    quantity: number;
}
```

---

## Authentication

### Flow

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│   Mobile    │         │   Laravel   │         │   Sanctum   │
│    App      │         │    API      │         │   Tokens    │
└─────────────┘         └─────────────┘         └─────────────┘
       │                       │                       │
       │ POST /api/login       │                       │
       │ {email, password}     │                       │
       │──────────────────────>│                       │
       │                       │ Create token          │
       │                       │──────────────────────>│
       │                       │                       │
       │                       │<──────────────────────│
       │                       │ token                 │
       │<──────────────────────│                       │
       │ {token, user}         │                       │
       │                       │                       │
       │ Store in SecureStore  │                       │
       │                       │                       │
       │ GET /api/v1/offerings │                       │
       │ Authorization: Bearer │                       │
       │──────────────────────>│                       │
       │                       │ Validate token        │
       │                       │──────────────────────>│
       │                       │                       │
       │                       │ Check user.status     │
       │                       │ (must be 'approved')  │
       │                       │                       │
       │<──────────────────────│                       │
       │ {data}                │                       │
       │                       │                       │
```

### Implementation

**Backend:**
- Laravel Sanctum generates API tokens
- Middleware checks token validity
- Custom middleware checks `user.status === 'approved'`

**Mobile:**
- Tokens stored in `expo-secure-store` (encrypted)
- Axios interceptor attaches token to all requests
- 401 response triggers automatic logout

---

## Database

### Engine

- **Production:** PostgreSQL 15+
- **Testing:** SQLite (in-memory for speed)

### Full-Text Search

```sql
-- Portuguese language index on drugs table
CREATE INDEX drugs_search_idx ON drugs
USING GIN (to_tsvector('portuguese',
    coalesce(product_name,'') || ' ' ||
    coalesce(substance,'') || ' ' ||
    coalesce(laboratory,'')
));
```

### Partial Unique Index

```sql
-- Only one pending/confirmed request per offering
CREATE UNIQUE INDEX medication_requests_unique_pending
ON medication_requests (medication_offering_id)
WHERE status IN ('pending', 'confirmed');
```

### Constraints

- Foreign keys with `CASCADE` on delete
- Check constraints for enums
- Unique constraints for email, phone, CRM

---

## Admin Panel (Filament v5)

### Structure

```
app/Filament/
├── Resources/
│   ├── UserResource.php              # User management + approval
│   ├── MedicationOfferingResource.php
│   ├── MedicationRequestResource.php
│   ├── MedicationAppointmentResource.php
│   └── ActivityLogResource.php       # Audit logs
├── Widgets/
│   ├── UserStatsWidget.php           # User statistics
│   ├── DonationStatsWidget.php       # Donation metrics
│   ├── OfferingsLineChart.php        # Offerings over time
│   ├── RequestsLineChart.php         # Requests over time
│   ├── TopDrugsWidget.php            # Most donated drugs
│   ├── TopDoctorsWidget.php          # Most active doctors
│   └── RecentActivityWidget.php      # Activity feed
└── Pages/
    └── Reports.php                   # Analytics dashboard
```

### Access

- URL: `/admin`
- Requires `role === 'admin'`
- Separate authentication from API

### Features

- User approval/rejection workflow
- View all system data (read-only for most)
- Activity logging (LGPD compliance)
- Dashboard with statistics
- Report generation

---

## Push Notifications

### Architecture

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│   Mobile    │         │   Laravel   │         │    Expo     │
│    App      │         │    API      │         │   Push API  │
└─────────────┘         └─────────────┘         └─────────────┘
       │                       │                       │
       │ POST /push-tokens     │                       │
       │ {token, device_type}  │                       │
       │──────────────────────>│                       │
       │                       │                       │
       │                       │ Store token           │
       │                       │                       │
       │                       │                       │
       │                       │ [Event occurs]        │
       │                       │ New request created   │
       │                       │                       │
       │                       │ POST to Expo          │
       │                       │──────────────────────>│
       │                       │                       │
       │                       │                       │──────┐
       │<──────────────────────│───────────────────────│<─────┘
       │ Push notification     │                       │
       │                       │                       │
```

### Events That Trigger Notifications

| Event | Recipient | Template |
|-------|-----------|----------|
| Request created | Doctor | "Novo pedido de medicamento" |
| Request confirmed | Receptor | "Pedido confirmado" |
| Request rejected | Receptor | "Pedido recusado" |
| Appointment proposed | Other party | "Nova proposta de agendamento" |
| Appointment accepted | Both | "Agendamento confirmado" |
| Delivery confirmed | Both | "Entrega confirmada" |

### Implementation

**Backend:**
```php
// app/Notifications/NewMedicationRequestNotification.php
class NewMedicationRequestNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['expo'];
    }

    public function toExpo($notifiable): ExpoMessage
    {
        return ExpoMessage::create()
            ->title('Novo Pedido')
            ->body('Você recebeu um novo pedido de medicamento')
            ->data(['request_id' => $this->request->id]);
    }
}
```

**Mobile:**
```typescript
// services/pushNotificationService.ts
export async function registerForPushNotifications() {
    const { status } = await Notifications.requestPermissionsAsync();
    if (status !== 'granted') return null;

    const token = await Notifications.getExpoPushTokenAsync();
    await api.post('/v1/push-tokens', {
        token: token.data,
        device_type: Platform.OS
    });

    return token.data;
}
```

---

## API Versioning

All API endpoints are versioned under `/api/v1/`.

### Strategy

- **URL versioning:** `/api/v1/`, `/api/v2/`
- **Controller namespaces:** `App\Http\Controllers\Api\V1\`
- **Resource namespaces:** `App\Http\Resources\Api\V1\`

### Adding a New Version

1. Create new controller namespace: `App\Http\Controllers\Api\V2\`
2. Create new resource namespace: `App\Http\Resources\Api\V2\`
3. Add routes in `routes/api.php` under new prefix
4. Document breaking changes

---

## Activity Logging

Uses `spatie/laravel-activitylog` for audit trail.

### Logged Models

- User (create, update, status change)
- MedicationOffering (create, update, delete)
- MedicationRequest (create, status change)
- MedicationAppointment (create, status change)

### Logged Fields

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['status', 'quantity', 'expires_at'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

### Viewing Logs

- Admin panel: `ActivityLogResource`
- Dashboard widget: Recent activity feed
- Filtered by entity type, date, user

---

## Testing Strategy

### Backend (Pest)

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   └── RegistrationTest.php
│   ├── MedicationOffering/
│   │   ├── ListTest.php
│   │   ├── CreateTest.php
│   │   └── UpdateTest.php
│   └── ...
└── Unit/
    ├── Actions/
    └── Rules/
```

**Patterns:**
- Feature tests for API endpoints
- Unit tests for Actions and Rules
- Factories for test data
- Database transactions (reset after each test)

### Mobile (Jest)

- Component tests with React Native Testing Library
- Service mocks for API calls
- Store tests for state management
