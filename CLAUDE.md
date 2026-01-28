# DoaFarma - AI Assistant Context

Medication donation platform connecting doctors with patients who need medications.

## Mandatory Workflow

**Before any task, read and follow `docs/AI_WORKFLOW.md`.**

This file defines the 5-phase workflow (Clarification → Design → Test Planning → Implementation → Review) that ALL AI-assisted development must follow. No exceptions.

## Quick Reference

- **Backend:** Laravel 12 (in `/web`)
- **Mobile:** React Native/Expo (in `/mobile`)
- **Auth:** Laravel Sanctum (token-based)
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
composer fix          # Run all quality checks (Pint + tests)
composer t            # Run tests only
./vendor/bin/pest     # Run Pest tests

# Mobile (from /mobile)
npm start             # Start Expo
npm run lint          # Lint code
npm test              # Run Jest tests
```

## Key Patterns

- **Actions:** Business logic in `app/Actions/` (single-purpose classes)
- **Form Requests:** Validation in `app/Http/Requests/` (with Portuguese messages)
- **Resources:** JSON transformation in `app/Http/Resources/Api/V1/`
- **Policies:** Authorization in `app/Policies/`
- **Enums:** Type-safe enums in `app/Enums/`

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
