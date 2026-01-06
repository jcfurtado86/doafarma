# DoaFarma - Copilot Instructions

Medication donation platform: Laravel backend + React Native mobile.

## Documentation

Read `/docs` before generating code:

- `CONTEXT.md` - Domain entities and business rules
- `ARCHITECTURE.md` - Project structure and patterns
- `CONVENTIONS.md` - Code style guidelines
- `AI_GUIDELINES.md` - AI development rules

## Critical Rules

1. **Actions pattern:** Put business logic in `app/Actions/`, not controllers
2. **Validation:** Use Form Requests with Portuguese messages
3. **API versioning:** New endpoints go in `Api/V1/`
4. **Authorization:** Use Policies for access control
5. **Mobile state:** Use Zustand stores, not Redux
6. **Types:** TypeScript required in mobile, strict types in PHP

## File Locations

| What | Where |
|------|-------|
| Business logic | `web/app/Actions/` |
| Validation | `web/app/Http/Requests/` |
| API responses | `web/app/Http/Resources/` |
| Mobile services | `mobile/services/` |
| Mobile stores | `mobile/stores/` |

## Before Suggesting Code

- Check existing patterns in similar files
- Follow the conventions in `/docs/CONVENTIONS.md`
- Do not introduce new patterns without justification
