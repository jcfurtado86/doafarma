# DoaFarma - Copilot Instructions

Medication donation platform: Laravel backend + React Native mobile.

## Mandatory Workflow

**You MUST read and follow `docs/AI_WORKFLOW.md` before any task.**

This workflow defines 5 phases: Clarification → Design → Test Planning → Implementation → Review. Do not skip phases. Do not write code before completing Phase 1-3.

## Documentation

Read `/docs` before generating code:

| File | Purpose |
|------|---------|
| `AI_WORKFLOW.md` | **Mandatory workflow (read first)** |
| `CONTEXT.md` | Domain entities and business rules |
| `ARCHITECTURE.md` | Project structure and patterns |
| `CONVENTIONS.md` | Code style guidelines |
| `DECISIONS.md` | Track decisions here |

## Critical Rules

1. **Actions pattern:** Business logic in `app/Actions/`, not controllers
2. **Validation:** Form Requests with Portuguese messages
3. **API versioning:** New endpoints in `Api/V1/`
4. **Authorization:** Use Policies
5. **Mobile state:** Zustand stores, not Redux
6. **Types:** TypeScript (mobile), strict types (PHP)
7. **Decisions:** Update `docs/DECISIONS.md` when clarifying requirements

## File Locations

| What | Where |
|------|-------|
| Business logic | `web/app/Actions/` |
| Validation | `web/app/Http/Requests/` |
| API responses | `web/app/Http/Resources/` |
| Mobile services | `mobile/services/` |
| Mobile stores | `mobile/stores/` |
