# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# DoaFarma - AI Assistant Context

Medication donation platform connecting doctors with patients who need medications.

## Mandatory Workflow

**Before any task, read and follow `docs/AI_WORKFLOW.md`.**

This file defines the 5-phase workflow (Clarification → Design → Test Planning → Implementation → Review) that ALL AI-assisted development must follow. No exceptions.

## Quick Reference

- **Backend:** Laravel 12 (in `/web`)
- **Mobile:** React Native/Expo (in `/mobile`)
- **Admin Panel:** Filament (in `web/app/Filament/`)
- **Auth:** Laravel Sanctum (token-based with refresh tokens)
- **Database:** PostgreSQL

## Documentation

All documentation is in `/docs`. **Use these as your primary reference and keep them updated.**

### Core Documentation (Read First)

| File | Purpose | When to Read |
|------|---------|--------------|
| `AI_WORKFLOW.md` | **Mandatory workflow for all tasks** | Always |
| `CONTEXT.md` | Business domain, entities, relationships, business rules | Before any feature work |
| `ARCHITECTURE.md` | Technical structure, patterns, code organization | Before implementing |
| `BUSINESS_FLOWS.md` | User flows, state transitions, authorization matrix | Before changing flows |

### Reference Documentation

| File | Purpose | When to Read |
|------|---------|--------------|
| `API_REFERENCE.md` | All API endpoints with examples | When working with API |
| `openapi.yaml` | OpenAPI 3.0 specification (machine-readable) | For API validation |
| `DEVELOPMENT_GUIDE.md` | Setup, adding features, testing, deployment | For new devs or complex tasks |
| `CONVENTIONS.md` | Code style, naming, patterns | Before writing code |
| `DECISIONS.md` | Architectural decisions and assumptions | Before proposing alternatives |

### Supplementary Documentation

| File | Purpose |
|------|---------|
| `ACTIVITY_LOGGING.md` | Spatie Activity Log setup and usage |
| `PUSH_NOTIFICATIONS.md` | Expo push notification implementation |

## Documentation Maintenance

**IMPORTANT:** When implementing changes, update the relevant documentation:

1. **New entity/model:** Update `CONTEXT.md` (add entity, relationships, business rules)
2. **New endpoint:** Update `API_REFERENCE.md` and `openapi.yaml`
3. **New flow/feature:** Update `BUSINESS_FLOWS.md` (add flow, state diagram)
4. **Architecture change:** Update `ARCHITECTURE.md`
5. **New pattern/convention:** Update `CONVENTIONS.md`
6. **Major decision:** Update `DECISIONS.md`

If documentation conflicts with codebase:
1. **Prioritize documentation** (it's the source of truth)
2. **Ask for confirmation** before implementing differently
3. **Update documentation** if the change is intentional

## Essential Commands

```bash
# Backend (from /web)
composer dev              # Start dev server (Laravel + queue + logs + Vite)
composer fix              # Run all quality checks (rector + phpstan + pint + tests)
composer t                # Run tests only
composer analyse          # Run PHPStan static analysis
composer pint             # Format code with Laravel Pint
./vendor/bin/pest tests/Feature/Auth/LoginTest.php  # Run single test file
./vendor/bin/pest --filter "test name"              # Run test by name

# Mobile (from /mobile)
npm start                 # Start Expo dev server
npm test                  # Run all Jest tests
npm test -- authStore.test.ts                       # Run single test file
npm test -- --testNamePattern="test name"            # Run test by name
npm run lint              # Lint code
npm run lint:fix          # Lint and auto-fix
npm run check-types       # TypeScript type checking
```

## Key Patterns

### Backend

- **Actions:** Business logic in `app/Actions/` — single-purpose classes with `execute()` method, injected into thin controllers
- **Form Requests:** Validation in `app/Http/Requests/` (with Portuguese messages)
- **Resources:** JSON transformation in `app/Http/Resources/Api/V1/`
- **Policies:** Authorization in `app/Policies/`
- **Enums:** Type-safe enums in `app/Enums/`
- **Routes:** Versioned API under `/api/v1/` in `routes/api.php`, with Sanctum + `approved` middleware

### Mobile

- **Routing:** Expo Router (file-based) in `app/` — `(auth)/` group for login/register, `(main)/` group for authenticated screens
- **State:** Zustand stores in `stores/` — one store per domain (auth, medicationOffering, etc.)
- **Services:** API calls in `services/` — use the configured Axios instance from `services/api.ts` which handles token injection and automatic refresh
- **Path alias:** `@/` maps to project root (e.g., `import { useAuthStore } from '@/stores/authStore'`)
- **Forms:** React Hook Form + Zod for validation schemas

## Entity Quick Reference

```
User (role: doctor|receptor|admin, status: pending|approved|rejected)
  └─ Doctor (crm, crm_uf)
       └─ MedicationOffering (status: available|reserved|completed)
            └─ MedicationRequest (status: pending|confirmed|rejected)
                 └─ MedicationAppointment (status: proposed|confirmed|completed)
                      └─ DoctorRating (1-5 stars)
```

## Rules

1. Follow `docs/AI_WORKFLOW.md` phases strictly
2. Read relevant docs before implementing (especially `CONTEXT.md` and `BUSINESS_FLOWS.md`)
3. Document decisions in `docs/DECISIONS.md`
4. Run `composer fix` before committing (backend)
5. Write validation messages in Portuguese
6. **Update documentation when making changes**
7. If documentation conflicts with codebase, prioritize documentation and ask for confirmation
