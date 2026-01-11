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

All documentation is in `/docs`:

| File | Purpose |
|------|---------|
| `AI_WORKFLOW.md` | **Mandatory workflow for all tasks** |
| `CONTEXT.md` | Business domain and entities |
| `ARCHITECTURE.md` | Technical structure and patterns |
| `CONVENTIONS.md` | Code style and naming |
| `AI_GUIDELINES.md` | Rules for AI-assisted development |
| `DECISIONS.md` | Architectural decisions and assumptions |

## Essential Commands

```bash
# Backend (from /web)
composer fix          # Run all quality checks
composer t            # Run tests

# Mobile (from /mobile)
npm start             # Start Expo
npm run lint          # Lint code
```

## Key Patterns

- **Actions:** Business logic in `app/Actions/`
- **Form Requests:** Validation in `app/Http/Requests/`
- **Resources:** JSON transformation in `app/Http/Resources/`
- **Policies:** Authorization in `app/Policies/`

## Rules

1. Follow `docs/AI_WORKFLOW.md` phases strictly
2. Document decisions in `docs/DECISIONS.md`
3. Run `composer fix` before committing (backend)
4. Write validation messages in Portuguese
5. If documentation conflicts with codebase, prioritize documentation and ask for confirmation
