# Architecture

## Repository Structure

```
doafarma/
├── web/                    # Laravel 12 backend
│   ├── app/
│   │   ├── Actions/        # Business logic (service layer)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/  # Versioned API
│   │   │   ├── Requests/   # Form validation
│   │   │   └── Resources/  # JSON transformation
│   │   ├── Models/         # Eloquent models
│   │   ├── Policies/       # Authorization
│   │   └── Rules/          # Custom validation rules
│   ├── database/migrations/
│   ├── routes/api.php
│   └── tests/
└── mobile/                 # React Native (Expo)
    ├── app/                # File-based routing (Expo Router)
    ├── components/         # Reusable UI components
    ├── services/           # API calls
    ├── stores/             # Zustand state management
    └── types/              # TypeScript definitions
```

## Backend Patterns

### Actions (Service Layer)
Business logic lives in `app/Actions/`. Controllers only handle HTTP concerns.

```php
// app/Actions/Auth/LoginAction.php
class LoginAction
{
    public function execute(string $email, string $password): ?User
    {
        // Business logic here
    }
}

// Controller just delegates
$user = (new LoginAction())->execute($email, $password);
```

### Form Requests
Validation and authorization in request classes.

```php
// app/Http/Requests/MedicationOfferingRequest.php
public function rules(): array
{
    return [
        'drug_id' => ['required', 'exists:drugs,id'],
        'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
    ];
}
```

### Resources
JSON transformation with conditional includes.

```php
// app/Http/Resources/MedicationOfferingResource.php
public function toArray($request): array
{
    return [
        'id' => $this->id,
        'drug' => new DrugResource($this->whenLoaded('drug')),
    ];
}
```

### Policies
Authorization rules per model.

```php
// app/Policies/MedicationOfferingPolicy.php
public function update(User $user, MedicationOffering $offering): bool
{
    return $user->doctor?->id === $offering->doctor_id;
}
```

## Mobile Patterns

### File-based Routing (Expo Router)
```
app/
├── (auth)/                 # Protected routes (requires login)
│   ├── _layout.tsx
│   └── dashboard.tsx
├── login.tsx
└── register/
    ├── doctor.tsx
    └── receptor.tsx
```

### Services
API calls centralized in `/services`.

```typescript
// services/medicationOfferingService.ts
export async function createOffering(data: CreateOfferingData) {
    const response = await api.post('/api/v1/medication-offerings', data);
    return response.data;
}
```

### Stores (Zustand)
Lightweight state management.

```typescript
// stores/authStore.ts
export const useAuthStore = create<AuthState>((set) => ({
    user: null,
    token: null,
    setAuth: (user, token) => set({ user, token }),
}));
```

## Authentication

- **Method:** Laravel Sanctum (token-based)
- **Storage:** `expo-secure-store` for tokens
- **Flow:** Login → receive token → attach to requests via Axios interceptor
- **401 Handling:** Automatic logout on expired tokens

## Database

- **Engine:** PostgreSQL (production), SQLite (testing)
- **Full-text search:** Portuguese language index on drugs table
- **Constraints:** Foreign keys with cascade deletes

## API Versioning

All API endpoints are versioned under `/api/v1/`. New versions should create new controller namespaces (e.g., `Api/V2/`).
