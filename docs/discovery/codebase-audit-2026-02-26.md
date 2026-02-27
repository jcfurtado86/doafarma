# DoaFarma Codebase Audit — 2026-02-26

## 1. Executive Summary

This audit covers the full DoaFarma monorepo: the Laravel 12 + Filament 5 web backend (`web/`) and the React Native Expo SDK 54 mobile app (`mobile/`). The codebase is a **medication donation platform** connecting doctors (donors) and receptors (recipients) across a complete request-to-appointment-to-delivery lifecycle.

The overall architecture is sound: the Actions pattern is consistently applied, the dual-token auth system is correctly implemented end-to-end, all 32 API controllers follow single-action patterns, and the API/mobile contract is tightly aligned. The test suite covers all API endpoints and critical flows.

The main gaps are: (1) **no address management API** — doctors register addresses only at registration with no subsequent CRUD, blocking doctors from updating pickup locations; (2) **no rate limiting on the primary login endpoint** (`POST /api/v1/auth/login`); (3) **push notification deep linking handles only `appointment_reminder`** — the `new_medication_request` type received by doctors has no navigation handler; and (4) **PHPStan is at level 6**, not the level 9 mandated by the global CLAUDE.md conventions.

**Overall Health**: Needs Attention
**Audit Scope**: `web/` (Laravel 12 + Filament 5) + `mobile/` (React Native Expo SDK 54)
**Database**: PostgreSQL MCP pointed to a different database — DB-dependent checks skipped where noted; schema evidence derived entirely from migrations.

---

## 2. Project Metadata

| Metric | Web | Mobile |
|--------|-----|--------|
| PHP/TS files | 18,296 PHP | 8,248 TS/TSX |
| Lines of code (approx) | ~48,700 | ~71,800 |
| Dependencies | 20 packages (composer.json) | 50 packages (package.json) |
| Test files | 62 PHP | 14 TS |
| Migrations | 25 | — |
| Contributors | 3 (aldairjunior33, Julio Furtado, Kauê de Magalhães) | 3 |
| Total commits | 398 | — |
| Last commit | 2026-02-26 | — |
| Branches | 51 total | — |

> Note: PHP file count includes vendor directory files. App-only PHP files are approximately 280 files across `app/`, `tests/`, `database/`, and `routes/`.

---

## 3. Web Backend Findings

### 3.1 Domain Model

**Status**: Healthy

Nine Eloquent models cover the full domain:

| Model | Table | Key Relationships | Factory |
|-------|-------|-------------------|---------|
| User | users | hasOne(Doctor), hasMany(Address), hasMany(MedicationRequest), hasMany(PushToken) | Yes |
| Doctor | doctors | belongsTo(User), hasMany(MedicationOffering), hasMany(DoctorRating) | Yes |
| Drug | drugs | hasMany(MedicationOffering) | Yes |
| Address | addresses | belongsTo(User) | Yes |
| MedicationOffering | medication_offerings | belongsTo(Doctor), belongsTo(Drug), hasMany(MedicationRequest) | Yes |
| MedicationRequest | medication_requests | belongsTo(User as receptor), belongsTo(MedicationOffering), hasOne(MedicationAppointment) | Yes |
| MedicationAppointment | medication_appointments | belongsTo(MedicationRequest), belongsTo(Address), hasOne(DoctorRating) | Yes |
| DoctorRating | doctor_ratings | belongsTo(MedicationAppointment), belongsTo(Doctor), belongsTo(User as receptor) | Yes |
| PushToken | push_tokens | belongsTo(User) | Yes |

All models have corresponding factories. No orphaned tables observed in migrations.

**Observations:**
- CPF is encrypted at rest (`'cpf' => 'encrypted'` cast in User) and a SHA-256 hash (`cpf_hash`) is used for uniqueness checks — good LGPD practice.
- `MedicationOffering` status is stored as a plain string (`'available'`, `'reserved'`, `'completed'`) rather than a PHP-backed enum. Scopes (`scopeAvailable`, etc.) are defined but the status column lacks enum type enforcement.
- No soft deletes on any model. Deletions cascade through foreign keys.
- Missing inverse relationship: `Address` has no `hasMany(MedicationAppointment)` despite appointments having an `address_id` FK.
- `Doctor` model does not use `LogsActivity` unlike User, MedicationOffering, MedicationRequest, and MedicationAppointment — activity log coverage is inconsistent.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/app/Models/`

### 3.2 Database Schema

**Status**: Healthy (assessed from migrations)

All 25 migrations have `down()` methods. Key schema notes:

- **Indexes**: `medication_offerings` has indexes on `status`, `expires_at`, and the composite `(status, expires_at)`. `medication_requests` has indexes on `receptor_id` and `medication_offering_id`. `medication_appointments` has indexes on `scheduled_date` and `(status, scheduled_date)`.
- **Partial unique index**: PostgreSQL-specific `CREATE UNIQUE INDEX` on `medication_requests (medication_offering_id) WHERE status = 'pending'` correctly prevents two pending requests on the same offering.
- **Missing index on `users.status`**: The `approved` middleware filters users by `status` frequently. No dedicated index was found in migrations (added in `2026_01_25_035300` only as a column, not an index).
- **Missing index on `users.email`**: The `email` column is `unique()` which creates an index implicitly — this is fine.
- **Missing index on `doctor_ratings.doctor_id`** and `doctor_ratings.receptor_id`: These are FK columns that are frequently queried but no explicit index beyond FK constraint.
- **`medication_appointments.status`** original default was `'scheduled'` (migration `2026_01_11_200000`), later changed to `'proposed'` values via `2026_01_17_193726_update_medication_appointment_status_values`. The TypeScript type reflects only `proposed | confirmed | completed` — consistent.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/database/migrations/`

### 3.3 API Endpoints

**Status**: Healthy

All 32 API v1 endpoints are registered under `/api/v1/` with consistent naming:

| Group | Endpoints | Auth | Approved |
|-------|-----------|------|----------|
| Auth | login, refresh, logout | Varies | No |
| Drugs | list, search | Yes | Yes |
| MedicationOfferings | list, store, show, update, delete, search | Yes | Yes |
| MedicationRequests | list, store, received, confirm, reject | Yes | Yes |
| MedicationAppointments | list, store, history, doctor-history, received, confirm-delivery-receptor, confirm-delivery-doctor, accept, counter-propose | Yes | Yes |
| PushTokens | register, delete | Yes | Yes |
| DoctorRatings | my-ratings, store, show-by-appointment, update, list-by-doctor | Yes | Yes |

**Observations:**
- There are **two login endpoints**: the legacy `POST /api/login` (via `ApiLoginController`) with no rate limiting and the versioned `POST /api/v1/auth/login` also with no rate limiting. Only the registration endpoints (`/api/register/doctor`, `/api/register/receptor`) have `throttle:10,1`.
- No address management endpoints exist. Addresses are created only during doctor registration (embedded in `DoctorRegistrationController`). No CRUD for addresses post-registration.
- API versioning is consistent — all new endpoints are under `v1`.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/routes/api.php`

### 3.4 Actions (Business Logic)

**Status**: Healthy

31 action classes are organized by domain under `app/Actions/`. All follow single-responsibility. Business logic is fully extracted from controllers, which only delegate to actions.

Key patterns observed:
- `CreateMedicationRequestAction` uses `DB::transaction` with `lockForUpdate()` to prevent race conditions on reservation — well implemented.
- `CreateMedicationAppointmentAction` uses the doctor's first address by default (`Address::where('user_id', $doctorUserId)->first()`) — this silently uses the first address, which is brittle if a doctor has multiple addresses.
- Push notifications are dispatched outside transactions (after `DB::transaction` returns) — correct ordering.
- No custom exception classes; `RuntimeException` and `ConflictHttpException` are used directly.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/app/Actions/`

### 3.5 Auth & Authorization

**Status**: Healthy

- Dual-token system (access + refresh) via Sanctum with separate `TokenAbility` enum values correctly enforced via `abilities:access` and `abilities:refresh` middleware.
- Token expiry is configurable via `sanctum.access_token_expiration_hours` and `sanctum.refresh_token_expiration_days`.
- `EnsureUserIsApproved` middleware returns structured JSON with `status` field for pending/rejected — correctly consumed by mobile.
- Four Policy classes cover `MedicationOffering`, `MedicationRequest`, `MedicationAppointment`, and `DoctorRating`. Policies are invoked via `FormRequest::authorize()`.
- **Security gap**: `POST /api/v1/auth/login` has no rate limiting. Any attacker can brute-force credentials.
- Password reset exists on the web auth routes (`/forgot-password`, `/reset-password`) but these are for the Filament admin web interface only, not for mobile users.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/routes/api.php:35-37`
- `/home/kaue/codes/tcc/doafarma/web/app/Http/Middleware/EnsureUserIsApproved.php`
- `/home/kaue/codes/tcc/doafarma/web/app/Actions/Auth/CreateTokenPairAction.php`

### 3.6 Filament Admin Panel

**Status**: Healthy

Filament resources cover:
- `UserResource` — full CRUD with create, edit, view, list pages
- `MedicationOfferingResource` — view and list (read-only admin view)
- `MedicationRequestResource` — view and list
- `MedicationAppointmentResource` — view and list
- `ActivityLogResource` — view and list (audit trail)

Dashboard widgets: `ActivityStatsWidget`, `DonationStatsWidget`, `MonthlyBarChart`, `OfferingsLineChart`, `RecentActivityWidget`, `RequestStatsWidget`, `RequestsLineChart`, `ReportsStatsWidget`, `StatusDoughnutChart`, `TopDocorsWidget`, `TopDrugsWidget`, `UserStatsWidget`.

**Observations:**
- No Filament resource for `Drug` — admins cannot manage the drug database from the panel. Drug imports are done via CLI command only.
- No Filament resource for `DoctorRating` — ratings are not visible in admin panel.
- No Filament resource for `PushToken` — not necessary for admin use but notable.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/app/Filament/`

### 3.7 Validation

**Status**: Healthy

18 Form Request classes cover all API endpoints. All use `authorize()` backed by Policies where applicable. No inline validation found in controllers.

Specific validation quality examples:
- `StoreMedicationOfferingRequest`: `lot_number` validated with regex `/^[A-Z0-9\-]+$/i`, `expires_at` must be after today and before +10 years — thorough.
- `SearchRequest` for drugs: `sort` validated against an allowlist via `Rule::in([...])` — prevents the SQL injection risk in `ListDrugAction::orderByRaw`.

**Observations:**
- `ListDrugAction` uses `orderByRaw('LOWER(' . $sort . ') ' . $order)` where `$sort` is the raw user input from `SearchRequest`. However, `SearchRequest` validates `sort` with `Rule::in(['id', 'product_name', 'substance', 'laboratory'])`, making it safe only if the request always comes through that Form Request. Direct action calls would be unsafe.
- `SearchDrugAction::applySorting` calls `in_array($sort, [...], true)` as a second-level guard — defensive duplication is acceptable but shows the pattern is fragile.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/app/Http/Requests/Api/V1/`
- `/home/kaue/codes/tcc/doafarma/web/app/Actions/Drug/ListDrugAction.php:21`

### 3.8 Testing

**Status**: Healthy

62 PHP test files covering:
- Feature tests for all 32 API endpoints
- Feature tests for auth flows (login, refresh, logout, token expiration)
- Feature tests for middleware (`EnsureUserIsApproved`)
- Feature tests for security (rate limiting, LGPD data protection)
- Feature tests for Filament Reports page
- Unit tests for Jobs (NotifyDoctorNewRequest, NotifyDoctorExpiringMedication, SendAppointmentReminder, ProcessDrugCsvImport)
- Unit tests for Models (Drug, MedicationOffering)
- Unit tests for the CSV import command

**Observations:**
- No Architecture tests (no `tests/Arch/ArchTest.php`). Global CLAUDE.md mandates architecture tests.
- PHPStan is at level 6 (`phpstan.neon`). Global CLAUDE.md mandates level 9. This gap means type issues that PHPStan at level 9 would catch are not tested.
- No coverage for `DoctorRegistrationController` (registration flow integration test is missing for doctors, though `RegisterReceptorTest.php` exists).
- No test for the address creation path during doctor registration.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/tests/`
- `/home/kaue/codes/tcc/doafarma/web/phpstan.neon:10`

### 3.9 Security

**Status**: Needs Attention

- CPF encrypted at rest with Laravel's `encrypted` cast — good.
- Activity logging excludes CPF and phone_number for LGPD compliance (User model comment explicitly states this).
- `SecurityHeaders` middleware adds `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, and `Strict-Transport-Security` (production only).
- CORS is configured for `api/*` with explicit allowed origins from `FRONTEND_URL` env var. Methods are explicitly listed.

**Gaps:**
- **No rate limiting on `POST /api/v1/auth/login`**. Registration has `throttle:10,1` but login does not, enabling brute-force attacks. Evidence: `routes/api.php:35-37`.
- `orderByRaw` in `ListDrugAction` and `SearchDrugAction` concatenates user-controlled values without parameterization. The upstream Form Request validation provides a guard, but the action itself is not safe if called directly.
- `DoctorRegistrationController` creates a token via `$user->createToken($request->device_name)->plainTextToken` using only a single token (no access/refresh pair), deviating from the standard login flow. The returned token has no ability constraint.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/routes/api.php`
- `/home/kaue/codes/tcc/doafarma/web/app/Actions/Drug/ListDrugAction.php:21`
- `/home/kaue/codes/tcc/doafarma/web/app/Http/Controllers/Auth/DoctorRegistrationController.php:36`

### 3.10 Code Quality

**Status**: Healthy

- `declare(strict_types=1)` is present in all PHP files reviewed — consistent.
- Pint formatting is configured and runs in the pre-commit hook.
- Rector is configured with `composer fix` script.
- PHPStan at level 6 — below the project standard of level 9 (global CLAUDE.md requirement).
- DocBlocks present on all public methods reviewed.
- No inline comments beyond intentional explanatory notes (e.g., LGPD compliance notes in User model).
- `MedicationOffering::status` uses plain strings instead of a backed enum — this inconsistency exists alongside the `UserRole`, `UserStatus`, and `TokenAbility` enums.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/phpstan.neon`

### 3.11 Infrastructure

**Status**: Healthy

- Docker Compose provided for PostgreSQL 17 local development.
- Queue system configured (`ShouldQueue` on all jobs, pre-commit runs `php artisan queue:listen`).
- Scheduled tasks: appointment reminders every 5 minutes, expiration alerts daily at 09:00.
- Four job classes: `NotifyDoctorNewRequestJob`, `NotifyDoctorExpiringMedicationJob`, `SendAppointmentReminderJob`, `ProcessDrugCsvImportJob`.
- No Laravel Notifications classes — all push notifications are sent directly via HTTP to the Expo push API in each Job class. This means push notification logic is duplicated across three job files (`sendPushNotification` private method repeated in each).
- No mail/email notifications for mobile users (no email confirmations, no approval notification emails).
- `TestPushNotificationCommand` exists for manual testing.
- Drug import via CSV via `ImportDrugsCommand`.

Evidence:
- `/home/kaue/codes/tcc/doafarma/web/routes/console.php`
- `/home/kaue/codes/tcc/doafarma/web/app/Jobs/`

---

## 4. Mobile App Findings

### 4.1 Navigation & Screens

**Status**: Healthy

Expo Router file-based routing structure:

```
app/
  index.tsx                         — Entry point (InitialScreen)
  login.tsx                         — Login
  register/
    index.tsx                       — Registration role selector
    doctor.tsx                      — Doctor registration
    receptor.tsx                    — Receptor registration
  modal.tsx                         — Generic modal
  +not-found.tsx                    — 404 screen
  (auth)/
    _layout.tsx                     — Auth guard (redirects if !isAuthenticated)
    dashboard.tsx                   — Role-based dashboard (Doctor/Receptor)
    pending-approval.tsx            — Pending/rejected status screen
    medication-offerings/
      index.tsx                     — Doctor: list own offerings
      create.tsx                    — Doctor: create offering
      edit/[id].tsx                 — Doctor: edit offering
    medication-requests/index.tsx   — Doctor: received requests list
    medication-appointments/index.tsx — Doctor: appointments list
    doctor/
      history.tsx                   — Doctor: donation history
      ratings.tsx                   — Doctor: view own ratings
    receptor/
      _layout.tsx
      (tabs)/
        _layout.tsx                 — Receptor tab navigation
        search.tsx                  — Receptor: search offerings
        requests.tsx                — Receptor: own requests
        appointments.tsx            — Receptor: own appointments
        history.tsx                 — Receptor: history (paginated)
      offering/[id].tsx             — Receptor: offering detail
      schedule/[requestId].tsx      — Receptor: schedule appointment
      rate/[appointmentId].tsx      — Receptor: rate doctor
```

**Observations:**
- Navigation correctly uses `(auth)` group guarded by `_layout.tsx` checking `isAuthenticated`.
- No explicit deep linking configuration (`expo-linking`), beyond notification tap handlers.
- The doctor UX uses a flat list-based dashboard with navigation buttons. Receptor UX uses tab navigation — this asymmetry is intentional but worth reviewing for doctor UX.
- No dedicated profile/settings screen for either role.

### 4.2 State Management

**Status**: Healthy

8 Zustand stores:

| Store | Responsibilities | Persistence |
|-------|-----------------|-------------|
| authStore | User session, tokens, push token | SecureStore (tokens), AsyncStorage (user) |
| networkStore | Connectivity state, reconnect callbacks | None |
| medicationOfferingStore | Doctor's offerings CRUD | None |
| medicationRequestStore | Request lifecycle | None |
| medicationAppointmentStore | Appointment lifecycle | None |
| doctorRatingStore | Rating CRUD | None |
| doctorRegistrationFormStore | Multi-step form state | None |
| receptorRegistrationStore | Registration form state | None |

**Observations:**
- `authStore` correctly imports raw `api` (not `apiClient`) only to manipulate `api.defaults.headers` — this is acceptable because it is not making HTTP calls through `api` directly, only setting the header default.
- Store cleanup on logout is complete: `authStore.logout()` clears SecureStore, AsyncStorage, and resets all state.
- No cross-store dependencies observed — good isolation.
- `networkStore` uses module-level `reconnectCallbacks` (a Set) outside reactive state — intentional and documented.

### 4.3 API Layer

**Status**: Healthy

- `services/api.ts` exports `apiClient` as the canonical client and `api` (raw axios) only for header manipulation.
- All service files (`authService`, `medicationOfferingService`, `medicationRequestService`, `medicationAppointmentService`, `doctorRatingService`, `pushNotificationService`, `doctorService`, `receptorService`) import `apiClient`.
- Token refresh retry queue uses module-level state with documented thread-safety reasoning.
- The axios bug workaround (`__retryResponse` pattern) is documented in `mobile/docs/AXIOS_BUG_INVESTIGATION.md`.

**Observations:**
- `apiClient` methods use `T = any` as default generic type. While this is acceptable for flexibility, it reduces type safety for callers that don't specify `T`.
- `doctorService.register` has no typed error handling (`catch (error) { ... throw error }`) — less robust than `receptorService` and `authService`.
- `receptorService.register` response interface `ReceptorRegistrationResponse` has `data.token: string` (single token), while the actual registration controller returns `{ user, token }` — this is correct but diverges from the login response shape (`access_token`, `refresh_token`).

### 4.4 Components

**Status**: Needs Attention

27 component entries in `components/`, including both files and directories. Reusable component library covers: `PrimaryButton`, `SecondaryButton`, `Input`, `Select`, `SearchableSelect`, `DateInput`, `TimeInput`, `Title`, `Caption`, `OfflineBanner`, `MedicationAppointmentCard`, `MedicationOfferingCard`, `MedicationRequestCard`, `SearchResultCard`, `AddressSelector`, `FilterButton`, `ListFooterLoader`, `AppointmentStatusBadge`, `RequestStatusBadge`, `CounterProposeModal`, `ArrowBackButton`.

**Observations:**
- `AddressSelector` uses `memo` and accessibility props (`a11y.radioButton`) — good pattern.
- Accessibility coverage is low: only **7 accessibility attribute usages** found across all screen files. Most interactive elements lack `accessibilityLabel` and `accessibilityRole`.
- All list screens use `FlatList` — `FlashList` (from `@shopify/flash-list`) is not installed. For large medication/drug lists, FlatList has known performance limitations.
- `dashboard.tsx` contains role-conditional rendering of 7 buttons with hardcoded navigation paths — no tab navigation for doctors. This screen will need more structure as features grow.

Evidence:
- `/home/kaue/codes/tcc/doafarma/mobile/components/`
- Accessibility count from grep across `app/**/*.tsx`

### 4.5 Forms & Validation

**Status**: Needs Attention

Forms identified:

| Form | Validation | Feature Flag |
|------|-----------|-------------|
| Login | react-hook-form + zod (via useFeatureForm) | Yes |
| Doctor Registration | react-hook-form + zod | Yes |
| Receptor Registration | react-hook-form + zod | Yes |
| Create Offering | react-hook-form | Partial |
| Edit Offering | react-hook-form | Partial |
| Schedule Appointment | Manual validation (no RHF) | No |
| Counter-Propose Appointment | Manual validation | No |

**Observations:**
- `schedule/[requestId].tsx` validates date/time manually via `validateInputs()` instead of `useFeatureForm`/react-hook-form — inconsistent with the project pattern.
- Feature flag `ENABLE_FRONTEND_VALIDATION` is toggled via `EXPO_PUBLIC_ENABLE_FRONTEND_VALIDATION`. When disabled, forms submit without client-side validation, relying entirely on server validation.
- The `config/endpoints.ts` file only exports auth endpoints; all other service files hardcode endpoint strings inline (e.g., `'/v1/medication-offerings'`). No single source of truth for non-auth endpoints.

Evidence:
- `/home/kaue/codes/tcc/doafarma/mobile/app/(auth)/receptor/schedule/[requestId].tsx:39-57`
- `/home/kaue/codes/tcc/doafarma/mobile/config/endpoints.ts`

### 4.6 Custom Hooks

**Status**: Healthy

4 custom hooks found:

| Hook | Purpose |
|------|---------|
| `useFeatureForm` | react-hook-form wrapper with feature-flag-gated zod validation |
| `usePagination` | Infinite scroll with deduplication, mounted-ref safety, and refresh support |
| `useNetworkStatus` | Subscribes to NetInfo and updates networkStore |
| `useCitiesByState` | Fetches Brazilian city list by state code (used in registration) |

`usePagination` is well-implemented: uses `useRef` for race condition safety, deduplicates items by `id`, and supports both initial load and refresh.

### 4.7 Error Handling & UX

**Status**: Needs Attention

- `ErrorBoundary` is re-exported from `expo-router` in `app/_layout.tsx` — the default Expo Router error boundary is in place.
- `OfflineBanner` component renders when `networkStore.isConnected` is false.
- Loading states are present on most screens.
- Toast messages (`react-native-toast-message`) used for success/error feedback.

**Observations:**
- **No empty state handling** on several list screens. If the doctor has no offerings, or the receptor has no requests, the FlatList renders an empty list with no feedback message.
- Pull-to-refresh is present on some screens (`receptor/(tabs)/requests.tsx`, `doctor/history.tsx`) but not on all list screens consistently.
- The `api.ts` interceptor auto-logs out on any 403 response. This means a temporary 403 (e.g., policy rejection on a single resource) would cause a full logout — overly aggressive.

Evidence:
- `/home/kaue/codes/tcc/doafarma/mobile/services/api.ts:176-178`

### 4.8 Push Notifications

**Status**: Partial

- `pushNotificationService.ts` handles permission requests, Expo push token retrieval, backend registration/deregistration, and foreground notification handlers.
- Token registered at login in `authStore.setupPushNotifications()`.
- Token deregistered on logout (fire-and-forget).

**Observations:**
- **Missing notification type handler**: `new_medication_request` is sent by the backend (`NotifyDoctorNewRequestJob`) but the mobile notification tap handler in `_layout.tsx` only handles `appointment_reminder`. Tapping a "new request" notification has no navigation effect.
- **Missing `expiring_medication` handler**: `NotifyDoctorExpiringMedicationJob` sends notifications with `type: 'expiring_medication'` (inferred) but there is no handler in mobile.
- `registerForPushNotificationsAsync` returns `null` on simulators (`!Device.isDevice`) — correct.

Evidence:
- `/home/kaue/codes/tcc/doafarma/mobile/app/_layout.tsx:76-87`
- `/home/kaue/codes/tcc/doafarma/web/app/Jobs/NotifyDoctorNewRequestJob.php:87`

### 4.9 Testing

**Status**: Needs Attention

14 TypeScript test files cover:

- `api.test.ts`, `api.edgecases.test.ts`, `api.integration.test.ts`, `api.processqueue.test.ts`, `api.retryerror.test.ts`, `api.statemanagement.test.ts` — thorough coverage of `services/api.ts`
- `authService.test.ts` — covers auth service
- `authStore.test.ts`, `networkStore.test.ts` — stores
- `appointmentRules.test.ts`, `dateHelpers.test.ts`, `timeHelpers.test.ts`, `toast.test.ts` — utils
- `BrazilianStates.test.ts` — constants

**Coverage configured for**: `stores/**` and `services/**` only.

**Gaps:**
- No tests for `medicationOfferingService`, `medicationRequestService`, `medicationAppointmentService`, `doctorRatingService`, `pushNotificationService`, `doctorService`, `receptorService`.
- No tests for any custom hooks (`usePagination`, `useFeatureForm`, `useCitiesByState`).
- No component tests.
- No screen/integration tests.
- `medicationAppointmentStore`, `medicationOfferingStore`, `medicationRequestStore`, `doctorRatingStore`, `doctorRegistrationFormStore`, `receptorRegistrationStore` have no tests.

Evidence:
- `/home/kaue/codes/tcc/doafarma/mobile/__tests__/`
- `/home/kaue/codes/tcc/doafarma/mobile/package.json:29-33`

### 4.10 Performance

**Status**: Needs Attention

- `usePagination` supports infinite scroll with deduplication.
- `useCallback` and `useMemo` are used in most list screens for `renderItem` and computed values.
- `AddressSelector` and `AddressItem` use `React.memo` — good.

**Observations:**
- **FlatList used throughout** (11 usages). `FlashList` from `@shopify/flash-list` is not a dependency — FlatList has known performance issues for long lists (drug catalog can be large).
- Some screens load **all items without pagination**: `medicationRequestService.list()` and `medicationAppointmentService.list()` return arrays without pagination metadata — unbounded queries.
- The `medicationAppointmentService` has both non-paginated (`list`) and paginated (`listHistoryPaginated`) variants — inconsistent.

Evidence:
- `/home/kaue/codes/tcc/doafarma/mobile/services/medicationRequestService.ts:19-26`
- `/home/kaue/codes/tcc/doafarma/mobile/services/medicationAppointmentService.ts:21-38`

### 4.11 TypeScript & Code Quality

**Status**: Healthy

- `tsconfig.json` extends `expo/tsconfig.base` with `"strict": true` — all strict checks enabled.
- No `@ts-ignore` or `@ts-expect-error` found in stores or services.
- `apiClient` methods use `T = any` as default, which is acceptable for a generic HTTP client.
- `@/` path alias consistently used across all files reviewed.
- ESLint with `eslint-config-expo` and Prettier configured.

### 4.12 Assets & Configuration

**Status**: Healthy

- `EXPO_PUBLIC_API_HOST` and `EXPO_PUBLIC_ENABLE_FRONTEND_VALIDATION` are the two configurable env vars.
- `config/storage.ts` and `config/endpoints.ts` centralize key constants (partial — non-auth endpoints are inline).
- `expo-secure-store` for tokens, `AsyncStorage` for non-sensitive user data — correct security posture.

---

## 5. Cross-Cutting Analysis

### 5.1 API Contract Alignment

| Endpoint | Web | Mobile | Notes |
|----------|-----|--------|-------|
| POST /api/v1/auth/login | LoginResource: `data.{user,access_token,refresh_token,expires_in,refresh_expires_in}` | `LoginResponse.data.*` matches | Aligned |
| POST /api/v1/auth/refresh | Returns `data.{access_token,expires_in}` | `RefreshTokenResponse.data.*` matches | Aligned |
| GET /api/v1/medication-offerings | MedicationOfferingResource: `{id,lot_number,expires_at,quantity,drug?,user?}` | MedicationOffering type matches | Aligned |
| GET /api/v1/medication-offerings/search | MedicationOfferingSearchResource | MedicationOfferingSearchResult: `{id,quantity,lot_number,expires_at,status,drug,doctor}` | Aligned — `status` field present |
| GET /api/v1/medication-appointments | MedicationAppointmentResource | MedicationAppointment type matches | Aligned |
| POST /api/register/doctor | Returns `{user, token}` (single token, no abilities) | `doctorService` returns `response.data` untyped | Mismatched — mobile has no typed interface for doctor registration response |
| POST /api/register/receptor | Returns `{data.{user,token}}` (single token) | `ReceptorRegistrationResponse.data.token` | Aligned for registration |
| No address endpoints | N/A | No address service in mobile | Gap — addresses can't be managed post-registration |

### 5.2 Auth Flow

The end-to-end auth lifecycle is well-implemented:

1. **Login**: `authService.login()` → `POST /api/v1/auth/login` → access + refresh tokens → stored in `SecureStore` via `authStore.saveSession()`.
2. **Request interceptor**: Reads access token from `SecureStore` and injects `Authorization: Bearer` header.
3. **401 handling**: Interceptor queues concurrent requests, uses refresh token to call `POST /api/v1/auth/refresh`, updates access token via `authStore.updateAccessToken()`, drains queue.
4. **Refresh failure**: Triggers `handleLogout()` which clears storage and redirects to login.
5. **403 handling**: Interceptor calls `handleLogout()` — aggressive (any 403 = logout).
6. **Logout**: `authStore.logout()` → clears SecureStore + AsyncStorage + removes axios header + fire-and-forget push token deregistration.

The `isLoggingOut` flag prevents concurrent logout calls. `resetApiState()` is called on new login to clear stale queue state.

### 5.3 Type Drift

| PHP (Enum/string literal) | TypeScript | Status |
|---------------------------|-----------|--------|
| `UserRole::Doctor = 'doctor'` | `'doctor'` in User interface | Aligned |
| `UserRole::Receptor = 'receptor'` | `'receptor'` in User interface | Aligned |
| `UserStatus::Pending = 'pending'` | `'pending'` in User interface | Aligned |
| `UserStatus::Approved = 'approved'` | `'approved'` in User interface | Aligned |
| `UserStatus::Rejected = 'rejected'` | `'rejected'` in User interface | Aligned |
| Appointment status: `'proposed','confirmed','completed'` | `MedicationAppointmentStatus` | Aligned |
| Request status: `'pending','confirmed','rejected'` | `MedicationRequestStatus` | Aligned |
| Offering status: `'available','reserved','completed'` | `MedicationOfferingStatus` | Aligned |

No type drift found. All PHP string literal values and TypeScript union types match.

### 5.4 Error Format Consistency

- **Validation errors (422)**: Laravel returns `{message, errors:{field:[messages]}}`. Mobile `api.ts` interceptor attaches `isValidationError: true` and `validationErrors: errors` to the rejected error object. `authService` and `receptorService` handle this correctly.
- **Approval errors (403 with `status` field)**: `EnsureUserIsApproved` returns `{message, status:'pending'/'rejected'}`. Mobile login screen checks for this by routing to `/(auth)/pending-approval`.
- **Server errors (500)**: `api.ts` interceptor shows a Toast automatically.
- **Offline errors**: `api.ts` request interceptor rejects with a custom `isOfflineError: true` error before making the network call.

Response format is consistent. The `data` wrapping via Laravel API Resources matches the `response.data.data` access pattern used in all service files.

---

## 6. Gap Analysis

### High Impact

| # | Category | Description | Impact | Effort | Evidence |
|---|----------|-------------|--------|--------|----------|
| 1 | SG | No rate limiting on `POST /api/v1/auth/login` — brute-force possible | High | S | `routes/api.php:35-37` |
| 2 | MF | No address management API — doctors cannot update pickup addresses after registration | High | M | No address routes in `routes/api.php`; `CreateMedicationAppointmentAction.php:24` uses `->first()` |
| 3 | IF | Push notification tap for `new_medication_request` has no navigation handler in mobile | High | S | `mobile/app/_layout.tsx:76-87` |
| 4 | MF | No password reset flow for mobile users — only web admin Filament users can reset passwords | High | L | No reset routes in `routes/api.php`; confirmed by mobile file search |

### Medium Impact

| # | Category | Description | Impact | Effort | Evidence |
|---|----------|-------------|--------|--------|----------|
| 5 | SG | `DoctorRegistrationController` issues a single unconstrained token (no access/refresh pair, no abilities) — inconsistent with login security model | Medium | M | `Auth/DoctorRegistrationController.php:36` |
| 6 | TD | `sendPushNotification` private method duplicated verbatim in 3 job classes | Medium | S | `Jobs/NotifyDoctorNewRequestJob.php`, `SendAppointmentReminderJob.php`, `NotifyDoctorExpiringMedicationJob.php` |
| 7 | TD | PHPStan level 6 instead of level 9 (project CLAUDE.md standard) | Medium | M | `phpstan.neon:10` |
| 8 | UG | No empty state UI on list screens — FlatList renders blank when no data | Medium | S | Multiple screens in `mobile/app/(auth)/` |
| 9 | TD | Non-auth API endpoints hardcoded in service files — no centralized endpoint registry | Medium | S | `services/medicationOfferingService.ts:14`, all other service files |
| 10 | TD | No Architecture tests in web (`tests/Arch/ArchTest.php` missing) | Medium | S | `tests/` directory |
| 11 | PG | Active appointments and requests loaded without pagination — unbounded queries | Medium | M | `medicationRequestService.ts:19-26`, `medicationAppointmentService.ts:21-38` |
| 12 | IF | Mobile test coverage missing for 6 service files and 6 store files | Medium | L | `mobile/__tests__/` |

### Low Impact

| # | Category | Description | Impact | Effort | Evidence |
|---|----------|-------------|--------|--------|----------|
| 13 | UG | Aggressive 403 logout — any policy rejection (e.g., trying to view another user's resource) causes full logout | Low | S | `services/api.ts:176-178` |
| 14 | UG | No accessibility labels on interactive elements — 7 usages across all screens | Low | M | `mobile/app/**/*.tsx` |
| 15 | PG | FlatList used for all lists — FlashList not installed for large-list performance | Low | S | All list screens; `package.json` dependencies |
| 16 | TD | `MedicationOffering.status` stored as plain string, not a PHP-backed enum (unlike UserRole, UserStatus) | Low | S | `Models/MedicationOffering.php:28-36` |
| 17 | TD | `Doctor` model does not use `LogsActivity` — incomplete audit trail | Low | S | `Models/Doctor.php` |
| 18 | TD | `schedule/[requestId].tsx` uses manual validation instead of `useFeatureForm` | Low | S | `app/(auth)/receptor/schedule/[requestId].tsx:39-57` |
| 19 | TD | Missing index on `users.status` column — queried frequently by `EnsureUserIsApproved` middleware | Low | S | No index migration for `users.status` |
| 20 | UG | Doctor UX uses a flat menu screen instead of tab navigation — inconsistent with receptor UX | Low | L | `app/(auth)/dashboard.tsx` |
| 21 | MF | No Filament admin resource for Drug and DoctorRating — admins cannot manage these via panel | Low | M | `app/Filament/` directory |

---

## 7. Recommendations

### Quick Wins (High/Medium Impact, Small Effort)

1. **Add rate limiting to login endpoint** (`routes/api.php`): Add `->middleware('throttle:5,1')` to `POST /api/v1/auth/login`. Resolves gap #1 (SG, High, S).

2. **Add notification tap handler for `new_medication_request`** (`mobile/app/_layout.tsx`): In `addNotificationResponseReceivedListener`, add a case for `data?.type === 'new_medication_request'` that navigates doctors to `/(auth)/medication-requests`. Resolves gap #3 (IF, High, S).

3. **Extract shared `sendPushNotification` utility** into a dedicated `PushNotificationHelper` class or service in `app/Services/`. All three job classes import it. Resolves gap #6 (TD, Medium, S).

4. **Create empty state components** for list screens. A simple reusable `EmptyState` component with an icon and message, shown when FlatList `data.length === 0`. Resolves gap #8 (UG, Medium, S).

5. **Centralize API endpoint constants** in `mobile/config/endpoints.ts`. Move all hardcoded endpoint strings from service files into the existing endpoints config. Resolves gap #9 (TD, Medium, S).

6. **Add Architecture tests** (`web/tests/Arch/ArchTest.php`) using Pest's `arch()` helper to enforce controller → action dependencies and strict types. Resolves gap #10 (TD, Medium, S).

7. **Add `status` enum for MedicationOffering** — create `App\Enums\MedicationOfferingStatus` and cast `status` column. Resolves gap #16 (TD, Low, S).

8. **Add index on `users.status`** via a new migration. Resolves gap #19 (TD, Low, S).

### Strategic Improvements (High Impact, Larger Effort)

9. **Implement address management API** (endpoints: `GET /api/v1/addresses`, `POST /api/v1/addresses`, `PUT /api/v1/addresses/{address}`, `DELETE /api/v1/addresses/{address}`). Requires: controller, action, form request, resource, policy, test, and mobile service + store + screen. This also fixes `CreateMedicationAppointmentAction` which silently picks `first()` address. Resolves gap #2 (MF, High, M).

10. **Implement password reset flow for mobile** — requires API endpoints (`POST /api/v1/auth/forgot-password`, `POST /api/v1/auth/reset-password`) and corresponding mobile screens. Resolves gap #4 (MF, High, L).

11. **Upgrade PHPStan to level 9** and resolve all resulting type errors. Resolves gap #7 (TD, Medium, M).

12. **Add pagination to active appointments and requests** lists. The `ListController` actions for `medication-appointments` and `medication-requests` should return paginated results, and mobile should use `usePagination`. Resolves gap #11 (PG, Medium, M).

### Tech Debt to Address

13. **Fix DoctorRegistrationController token issuance**: Replace the single `createToken()` call with `CreateTokenPairAction` to issue a proper access + refresh token pair with abilities. Resolves gap #5 (SG, Medium, M).

14. **Add mobile test coverage** for the 6 service files and 6 store files currently without tests. Resolves gap #12 (IF, Medium, L).

15. **Replace 403 → logout with 403 → error message** for non-auth 403 responses (policy rejections). Check if the 403 body contains `status: 'pending'` or `status: 'rejected'` before triggering logout vs. showing an error toast. Resolves gap #13 (UG, Low, S).

16. **Add `LogsActivity` to Doctor model** for consistent audit trail. Resolves gap #17 (TD, Low, S).

17. **Add FlashList** (`@shopify/flash-list`) as a replacement for FlatList on large-data screens (drug search, medication offering search). Resolves gap #15 (PG, Low, S for dependency + M for migration).

---

**Next Step**: Run `/product-discovery docs/discovery/codebase-audit-2026-02-26.md` to start product discovery informed by this audit.
