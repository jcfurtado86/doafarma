# DoaFarma - AI Assistant Context

This is a medication donation platform connecting doctors with patients who need medications.

## Quick Reference

- **Backend:** Laravel 12 (in `/web`)
- **Mobile:** React Native/Expo (in `/mobile`)
- **Auth:** Laravel Sanctum (token-based)
- **Database:** PostgreSQL

## Documentation

All detailed documentation is in `/docs`:

- `CONTEXT.md` - Business domain and entities
- `ARCHITECTURE.md` - Technical structure and patterns
- `CONVENTIONS.md` - Code style and naming
- `AI_GUIDELINES.md` - Rules for AI-assisted development
- `DECISIONS.md` - Architectural decisions and assumptions

## Essential Commands

```bash
# Backend (from /web)
composer fix          # Run all quality checks (rector + phpstan + pint + pest)
composer t            # Run tests
php artisan serve     # Start dev server

# Mobile (from /mobile)
npm start             # Start Expo
npm run lint          # Lint code
```

## Key Patterns

- **Actions:** Business logic in `app/Actions/` (e.g., `LoginAction`, `SearchDrugAction`)
- **Form Requests:** Validation in `app/Http/Requests/`
- **Resources:** JSON transformation in `app/Http/Resources/`
- **Policies:** Authorization in `app/Policies/`

## Before Making Changes

1. Read the relevant `/docs` files
2. Follow existing patterns in the codebase
3. Run `composer fix` before committing (backend)
4. Write validation messages in Portuguese

If documentation conflicts with the current codebase, prioritize documentation and ask for confirmation.