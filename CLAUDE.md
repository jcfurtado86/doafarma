# CLAUDE.md

## Project Overview

DoaFarma is a medication donation platform connecting doctors (who donate medications) with receptors (who receive them). Monorepo with two applications:

- **`web/`** — Laravel 12 + Filament 5 admin panel (PHP 8.5). Backend API + admin web interface.
- **`mobile/`** — React Native (Expo SDK 54) mobile app consuming the Laravel API.

Three user roles: **doctor**, **receptor**, **admin**. Doctors and receptors use the mobile app; admins use the Filament web panel. Users require admin approval after registration (`EnsureUserIsApproved` middleware).

## Common Commands

### Web (Laravel) — run from `web/`

```bash
composer dev                    # Start all dev servers (serve + queue + pail + vite)
composer t                      # Run tests (Pest)
composer tp                     # Run tests in parallel
composer td                     # Run only dirty tests (changed files)
composer tf -- --filter=TestName  # Run a single test by name
./vendor/bin/pest --filter=TestName  # Alternative: run single test
composer analyse                # Run PHPStan (level 6)
composer pint                   # Format code with Laravel Pint
./vendor/bin/rector             # Run Rector refactoring
composer fix                    # Run rector + phpstan + pint + pest --parallel
```

Database: PostgreSQL 17 via Docker (`docker compose up -d` from `web/`). Tests use SQLite in-memory.

### Mobile (React Native) — run from `mobile/`

```bash
npx expo start                  # Start Expo dev server
npm run android                 # Start on Android
npm run ios                     # Start on iOS
npm test                        # Run Jest tests
npm test -- --testPathPattern=stores/authStore  # Run a specific test file
npm run check-types             # TypeScript type checking (tsc --noemit)
npm run lint                    # ESLint
npm run lint:fix                # ESLint with autofix
```

API host configured via `EXPO_PUBLIC_API_HOST` env var (defaults to `192.168.0.1`).

### Pre-commit Hook (Husky)

Runs automatically on commit. Web: PHPStan → tests → Pint. Mobile: ESLint → Jest → Prettier.

## Architecture

### Web Backend

- **Single-action controllers**: `app/Http/Controllers/Api/V1/` — one action per controller (StoreController, ListController, SearchController)
- **Actions pattern**: Business logic in `app/Actions/` by domain (Auth, Drug, MedicationOffering, MedicationRequest, MedicationAppointment, PushToken, DoctorRating)
- **Auth**: Laravel Sanctum with dual-token system (access + refresh, `TokenAbility` enum)
- **Admin panel**: Filament resources in `app/Filament/`
- **API versioning**: `/api/v1/` prefix
- **Enums**: `UserRole`, `UserStatus`, `TokenAbility`
- **API docs**: Auto-generated via `dedoc/scramble`

### Mobile App

- **Routing**: Expo Router (file-based) with `(auth)` group for authenticated screens
- **State management**: Zustand stores in `stores/` (one per domain)
- **API layer**: `services/api.ts` exports `apiClient` (not raw `api`) — handles token refresh, retry queue. All service files MUST use `apiClient`.
- **Forms**: `react-hook-form` + `zod` for validation
- **Path alias**: `@/` maps to project root
- **Feature flags**: `config/featureFlags.ts` — toggled via `EXPO_PUBLIC_ENABLE_FRONTEND_VALIDATION`
- **Custom hooks**: `useFeatureForm.ts` (form management), `usePagination.ts` (infinite scroll)
- **Test coverage**: Configured for `stores/` and `services/` directories

### Code Style

- **PHP**: PSR-12 with strict types enforced by Pint. Aligned `=` and `=>` operators.
- **TypeScript**: Prettier — single quotes, 100 char width, trailing commas (es5), 2-space indent.
