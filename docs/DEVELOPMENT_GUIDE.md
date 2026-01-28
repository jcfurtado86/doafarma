# Development Guide

Complete guide for setting up and developing on the DoaFarma platform.

---

## Prerequisites

### Required Software

| Software | Version | Purpose |
|----------|---------|---------|
| PHP | 8.2+ | Backend runtime |
| Composer | 2.x | PHP dependency management |
| Node.js | 18+ | Mobile development |
| npm | 9+ | Node package management |
| PostgreSQL | 15+ | Production database |
| SQLite | 3.x | Test database |
| Git | 2.x | Version control |

### Recommended Tools

- **Expo CLI**: `npm install -g expo-cli`
- **Laravel Installer**: `composer global require laravel/installer`
- **TablePlus** or **DBeaver**: Database GUI

---

## Getting Started

### 1. Clone Repository

```bash
git clone https://github.com/jcfurtado86/doafarma.git
cd doafarma
```

### 2. Backend Setup

```bash
cd web

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=doafarma
# DB_USERNAME=postgres
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed database (optional, includes ANVISA drug catalog)
php artisan db:seed

# Start development server
php artisan serve
```

**Backend will be available at:** `http://localhost:8000`

### 3. Mobile Setup

```bash
cd mobile

# Install dependencies
npm install

# Configure API URL in .env (or constants)
# API_URL=http://localhost:8000/api

# Start Expo development server
npm start
```

**Options after starting:**
- Press `a` for Android emulator
- Press `i` for iOS simulator
- Scan QR code with Expo Go app

---

## Project Structure

### Backend (`/web`)

```
web/
├── app/
│   ├── Actions/              # Business logic (single-purpose classes)
│   │   ├── Auth/            # Authentication actions
│   │   ├── Drug/            # Drug catalog actions
│   │   ├── MedicationOffering/
│   │   ├── MedicationRequest/
│   │   ├── MedicationAppointment/
│   │   ├── DoctorRating/
│   │   └── PushToken/
│   ├── Enums/               # PHP enums (UserRole, UserStatus)
│   ├── Filament/            # Admin panel (Filament v5)
│   │   ├── Resources/       # CRUD resources
│   │   ├── Widgets/         # Dashboard widgets
│   │   └── Pages/           # Custom pages
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/V1/      # API controllers (versioned)
│   │   ├── Middleware/      # Custom middleware
│   │   ├── Requests/        # Form validation
│   │   └── Resources/       # JSON transformers
│   ├── Models/              # Eloquent models
│   ├── Notifications/       # Push notification classes
│   ├── Policies/            # Authorization policies
│   └── Rules/               # Custom validation rules
├── config/                  # Configuration files
├── database/
│   ├── factories/           # Model factories (testing)
│   ├── migrations/          # Database schema
│   └── seeders/             # Initial data
├── routes/
│   ├── api.php             # API routes
│   └── web.php             # Web routes (admin)
└── tests/
    ├── Feature/            # Integration tests
    └── Unit/               # Unit tests
```

### Mobile (`/mobile`)

```
mobile/
├── app/                     # Expo Router screens
│   ├── (auth)/             # Protected routes
│   │   ├── doctor/         # Doctor-specific screens
│   │   ├── receptor/       # Receptor-specific screens
│   │   ├── medication-offerings/
│   │   ├── medication-requests/
│   │   └── medication-appointments/
│   ├── register/           # Registration screens
│   ├── _layout.tsx         # Root layout
│   ├── index.tsx           # Entry point
│   └── login.tsx           # Login screen
├── components/              # Reusable UI components
├── services/               # API service layer
├── stores/                 # Zustand state stores
├── types/                  # TypeScript definitions
├── constants/              # App constants
└── utils/                  # Helper functions
```

---

## Development Workflow

### Adding a New Feature (Backend)

1. **Create Migration** (if needed)
   ```bash
   php artisan make:migration create_feature_table
   ```

2. **Create Model**
   ```bash
   php artisan make:model Feature
   ```

3. **Create Action**
   ```php
   // app/Actions/Feature/CreateFeatureAction.php
   class CreateFeatureAction
   {
       public function execute(array $data): Feature
       {
           return Feature::create($data);
       }
   }
   ```

4. **Create Form Request**
   ```bash
   php artisan make:request StoreFeatureRequest
   ```

5. **Create Controller**
   ```php
   // app/Http/Controllers/Api/V1/Feature/StoreController.php
   class StoreController extends Controller
   {
       public function __invoke(StoreFeatureRequest $request, CreateFeatureAction $action)
       {
           $feature = $action->execute($request->validated());
           return new FeatureResource($feature);
       }
   }
   ```

6. **Create Resource**
   ```bash
   php artisan make:resource FeatureResource
   ```

7. **Add Route**
   ```php
   // routes/api.php
   Route::post('/v1/features', Feature\StoreController::class);
   ```

8. **Write Tests**
   ```bash
   php artisan make:test Feature/CreateFeatureTest
   ```

### Adding a New Entity

1. Create Migration
2. Create Model with:
   - Fillable attributes
   - Relationships
   - `LogsActivity` trait (for audit)
3. Create Factory
4. Create Seeder
5. Create Policy
6. Register Policy in `AuthServiceProvider`
7. Create Filament Resource (admin)

### Adding Mobile Screen

1. **Create Screen File**
   ```typescript
   // app/(auth)/feature/index.tsx
   export default function FeatureScreen() {
       return <View>...</View>;
   }
   ```

2. **Create Service** (if API call needed)
   ```typescript
   // services/featureService.ts
   export async function getFeatures() {
       const response = await api.get('/v1/features');
       return response.data;
   }
   ```

3. **Create Store** (if state needed)
   ```typescript
   // stores/featureStore.ts
   export const useFeatureStore = create<FeatureState>((set) => ({
       features: [],
       fetchFeatures: async () => {
           const data = await getFeatures();
           set({ features: data });
       },
   }));
   ```

---

## Testing

### Backend Tests (Pest)

```bash
# Run all tests
composer test
# or
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/MedicationOfferingTest.php

# Run with coverage
./vendor/bin/pest --coverage

# Run specific test
./vendor/bin/pest --filter="it_creates_offering"
```

### Test Patterns

```php
// Feature Test Example
it('allows doctor to create offering', function (): void {
    // Arrange
    $doctor = Doctor::factory()->create();
    $drug = Drug::factory()->create();

    // Act
    $response = $this->actingAs($doctor->user)
        ->postJson('/api/v1/medication-offerings', [
            'drug_id' => $drug->id,
            'lot_number' => 'LOT123',
            'expires_at' => now()->addYear()->format('Y-m-d'),
            'quantity' => 50,
        ]);

    // Assert
    $response->assertCreated();
    $this->assertDatabaseHas('medication_offerings', [
        'drug_id' => $drug->id,
        'lot_number' => 'LOT123',
    ]);
});
```

### Mobile Tests (Jest)

```bash
cd mobile

# Run all tests
npm test

# Run with watch
npm test -- --watch
```

---

## Code Quality

### Backend

```bash
# Fix code style (Laravel Pint)
composer pint
# or
./vendor/bin/pint

# Static analysis (PHPStan) - if configured
./vendor/bin/phpstan analyse

# Run all quality checks
composer fix
```

### Mobile

```bash
# Lint
npm run lint

# Type check
npx tsc --noEmit
```

### Pre-commit Checklist

Before committing:
1. `composer fix` (backend)
2. `npm run lint` (mobile)
3. `composer test` (backend)
4. `npm test` (mobile)

---

## Database

### Migrations

```bash
# Create migration
php artisan make:migration add_field_to_table

# Run migrations
php artisan migrate

# Rollback
php artisan migrate:rollback

# Fresh (drop all + migrate)
php artisan migrate:fresh

# Fresh with seed
php artisan migrate:fresh --seed
```

### Seeders

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=DrugSeeder
```

### Useful Tinker Commands

```bash
php artisan tinker

# Count records
>>> User::count()
>>> MedicationOffering::where('status', 'available')->count()

# Find user
>>> User::where('email', 'test@example.com')->first()

# Check relationships
>>> $doctor = Doctor::first()
>>> $doctor->offerings
>>> $doctor->user
```

---

## Admin Panel

Access the Filament admin at: `http://localhost:8000/admin`

### Creating Admin User

```bash
php artisan make:filament-user
```

Or via tinker:
```php
User::create([
    'name' => 'Admin',
    'email' => 'admin@doafarma.com',
    'password' => bcrypt('password'),
    'role' => 'admin',
    'status' => 'approved',
]);
```

### Filament Resources

| Resource | Purpose |
|----------|---------|
| UserResource | Manage users, approve/reject registrations |
| MedicationOfferingResource | View all offerings |
| MedicationRequestResource | View all requests |
| MedicationAppointmentResource | View all appointments |
| ActivityLogResource | View system activity (LGPD) |

---

## Environment Variables

### Backend (`web/.env`)

```env
# Application
APP_NAME=DoaFarma
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=doafarma
DB_USERNAME=postgres
DB_PASSWORD=

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:8000

# Push Notifications (Expo)
EXPO_ACCESS_TOKEN=your_expo_token
```

### Mobile (`mobile/.env` or constants)

```env
API_URL=http://localhost:8000/api
```

For physical devices, use your machine's IP instead of localhost.

---

## Common Issues

### Backend

**"Class not found" errors:**
```bash
composer dump-autoload
```

**Migration errors:**
```bash
php artisan migrate:fresh
```

**Permission errors (storage/logs):**
```bash
chmod -R 775 storage bootstrap/cache
```

### Mobile

**Metro bundler issues:**
```bash
npx expo start --clear
```

**iOS build fails:**
```bash
cd ios && pod install && cd ..
```

**API connection fails on physical device:**
- Use machine IP instead of localhost
- Ensure device is on same network
- Check firewall settings

---

## Deployment

### Backend Deployment Checklist

1. Set `APP_ENV=production`
2. Set `APP_DEBUG=false`
3. Run `composer install --optimize-autoloader --no-dev`
4. Run `php artisan config:cache`
5. Run `php artisan route:cache`
6. Run `php artisan view:cache`
7. Run `php artisan migrate --force`
8. Set up queue worker (if using queues)
9. Set up scheduler (cron)

### Mobile Deployment

1. Update `app.json` version
2. Build with EAS: `eas build --platform all`
3. Submit to stores: `eas submit`

---

## Resources

### Documentation
- [Laravel 12 Docs](https://laravel.com/docs/12.x)
- [Expo Docs](https://docs.expo.dev)
- [Filament v5 Docs](https://filamentphp.com/docs/5.x)
- [Zustand Docs](https://docs.pmnd.rs/zustand)

### Project Documentation
- `docs/CONTEXT.md` - Business domain
- `docs/ARCHITECTURE.md` - Technical structure
- `docs/BUSINESS_FLOWS.md` - User flows
- `docs/API_REFERENCE.md` - API endpoints
- `docs/CONVENTIONS.md` - Code standards
- `docs/DECISIONS.md` - Architectural decisions
