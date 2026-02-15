# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

DoaFarma is a medication donation platform connecting doctors (who donate medications) with receptors (who receive them). It's a monorepo with two main applications:

- **`web/`** — Laravel 12 + Filament 5 admin panel (PHP 8.5). Serves as both the backend API and the admin web interface.
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
php artisan test --parallel     # Run tests in parallel (used by pre-commit hook)
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

Runs automatically on commit. For web changes: PHPStan → tests → Pint formatting. For mobile changes: ESLint → Jest tests → Prettier formatting.

## Architecture

### Web Backend

- **Single-action controllers**: API controllers under `app/Http/Controllers/Api/V1/` — each controller handles one action (e.g., `StoreController`, `ListController`, `SearchController`)
- **Actions pattern**: Business logic in `app/Actions/` organized by domain (Auth, Drug, MedicationOffering, MedicationRequest, MedicationAppointment, PushToken, DoctorRating)
- **API versioning**: Routes prefixed with `/api/v1/`
- **Auth**: Laravel Sanctum with dual-token system (access + refresh tokens, using `TokenAbility` enum)
- **Admin panel**: Filament resources in `app/Filament/` with dashboard widgets and a Reports page
- **Policies**: Authorization via `app/Policies/` for medication offerings, requests, appointments, and doctor ratings
- **API Resources**: Response transformation in `app/Http/Resources/`
- **Enums**: `UserRole` (doctor/receptor/admin), `UserStatus`, `TokenAbility`
- **Jobs**: Background jobs for push notifications and drug CSV imports
- **Artisan commands**: Drug import, appointment reminders, expiration alerts
- **Activity logging**: Uses `spatie/laravel-activitylog`
- **API docs**: Auto-generated via `dedoc/scramble`

### Mobile App

- **Routing**: Expo Router (file-based) with `(auth)` group for authenticated screens
- **State management**: Zustand stores in `stores/` (one per domain: auth, medicationOffering, medicationRequest, medicationAppointment, doctorRating, plus registration form stores)
- **API layer**: `services/api.ts` exports `apiClient` wrapper (not raw `api`) — handles token refresh, retry queue, and an axios bug workaround. All service files should use `apiClient`.
- **Services**: One per domain in `services/`, each using `apiClient` for HTTP calls
- **Types**: TypeScript interfaces in `types/` organized by domain
- **Forms**: `react-hook-form` + `zod` for validation
- **Path alias**: `@/` maps to project root (configured in tsconfig and eslint)
- **Feature flags**: `config/featureFlags.ts` — frontend validation toggled via `EXPO_PUBLIC_ENABLE_FRONTEND_VALIDATION`
- **Custom hooks**: `hooks/useFeatureForm.ts` (form management), `hooks/usePagination.ts` (infinite scroll)
- **Components**: Reusable UI components in `components/` (Input, Select, buttons, cards, modals, badges)
- **Push notifications**: Expo Notifications with backend token registration
- **Test coverage**: Configured for `stores/` and `services/` directories

### Code Style

- **PHP**: PSR-12 with strict types (`declare(strict_types = 1)`) enforced by Pint. Aligned `=` and `=>` operators. Blank lines before control structures.
- **TypeScript/React Native**: Prettier with single quotes, 100 char print width, trailing commas (es5), 2-space indentation.