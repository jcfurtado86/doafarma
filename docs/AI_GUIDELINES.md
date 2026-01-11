# AI Development Guidelines

Rules for AI-assisted development in this project.

## General Principles

1. **Follow existing patterns.** Before creating something new, find a similar existing implementation.
2. **Read before writing.** Always read related files before making changes.
3. **Minimal changes.** Only change what's necessary. Don't refactor unrelated code.
4. **No invented requirements.** Don't add features or validations not explicitly requested.

## Backend Rules

### Do

- Put business logic in `app/Actions/`
- Use Form Requests for validation
- Use Resources for JSON responses
- Use Policies for authorization
- Write validation messages in Portuguese
- Run `composer fix` to check code quality

### Don't

- Put business logic in controllers
- Create repositories (use Eloquent directly or Actions)
- Add packages without explicit request
- Create abstract classes for single implementations
- Add comments for obvious code

## Mobile Rules

### Do

- Use Zustand for state management
- Use the configured Axios instance from `services/api.ts`
- Handle loading and error states
- Use TypeScript strict mode
- Follow the component structure in CONVENTIONS.md

### Don't

- Use Redux or Context for global state
- Make API calls directly in components
- Create class components
- Use `any` type without justification
- Add new dependencies without explicit request

## Code Generation Checklist

Before generating code, verify:

- [ ] Read the relevant existing files
- [ ] Identified the pattern used for similar features
- [ ] Checked CONVENTIONS.md for naming rules
- [ ] Confirmed the requirement is explicit, not assumed

After generating code, verify:

- [ ] Code follows existing patterns
- [ ] No new patterns introduced without justification
- [ ] Types are explicit (no implicit any)
- [ ] Validation messages are in Portuguese (backend)

## Common Mistakes to Avoid

1. **Creating service classes in Laravel** - Use Actions instead
2. **Adding try-catch everywhere** - Let Laravel handle exceptions
3. **Over-validating** - Trust internal code, validate at boundaries
4. **Creating DTOs** - Use typed arrays or request objects
5. **Adding logging everywhere** - Only log meaningful events
6. **Creating interfaces for single implementations** - YAGNI

## When Unsure

1. Ask for clarification before implementing
2. State assumptions explicitly
3. Prefer the simpler solution
4. Look at recent commits for context

## File References

When suggesting changes, always reference the specific file:

```
// In web/app/Actions/MedicationOffering/CreateMedicationOfferingAction.php
```

This helps verify the suggestion matches existing code.

AI assistants must not silently propagate existing technical debt.
If a pattern seems inconsistent, flag it instead of replicating it.

## Decision Tracking Rule

Whenever a feature discussion results in:
- clarification of ambiguous requirements
- assumptions about business rules
- scope limitation or expansion
- product-level interpretation of a vague task

You MUST:
1. Explicitly state that a decision was made
2. Propose an entry to docs/DECISIONS.md
3. Ask for confirmation before writing it
