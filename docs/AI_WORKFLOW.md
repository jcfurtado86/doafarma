# AI-Assisted Development Workflow

This document defines the mandatory workflow for AI-assisted development in this project.
All AI tools (Claude, GitHub Copilot, etc.) must follow this workflow strictly.

The human communicates intent in natural language.
The AI is responsible for structuring, clarifying, documenting, and enforcing this workflow.

This document is the source of truth for how tasks are executed.

---

## Fundamental Principles

1. The human does NOT structure technical documents manually.
2. The human explains requirements, decisions, and constraints in natural language (text or transcription).
3. The AI MUST:
   - Interpret the human input
   - Identify ambiguities
   - Ask clarifying questions when required
   - Produce structured artifacts
4. The AI MUST NOT:
   - Invent requirements
   - Infer business rules without confirmation
   - Skip workflow phases
5. Any relevant product or architectural decision MUST be documented in `docs/DECISIONS.md`.

---

## Language Policy

- Human input: Portuguese (PT-BR) or natural spoken language
- Technical artifacts and documentation: English
- The AI must translate and normalize information when needed

---

## Workflow Overview

All tasks MUST follow the phases below, in order.

The AI is responsible for identifying the current phase and acting accordingly.

---

## Artifact Persistence Rule

Each phase MUST produce a **concrete, versioned artifact** (file) as its output.

### Principles

1. **Artifacts are the source of truth.** The artifact produced by a phase is the only required input for the next phase.
2. **Chats are disposable.** Once a phase artifact is generated and approved, the chat context can be discarded. A new chat can start the next phase using only the artifact.
3. **Artifacts enable context independence.** Each phase can begin in a fresh conversation with full context restored from the artifact.

### Artifact Location

All feature artifacts MUST be stored in:

```
docs/features/<feature-slug>/phase-<N>-<artifact-type>.md
```

Example:
```
docs/features/search-active-medication-offerings/phase-1-feature-spec.md
docs/features/search-active-medication-offerings/phase-2-technical-design.md
docs/features/search-active-medication-offerings/phase-3-test-plan.md
```

### Required Artifacts by Phase

| Phase | Artifact | Description |
|-------|----------|-------------|
| 1 | `phase-1-feature-spec.md` | Feature specification with scope, constraints, acceptance criteria |
| 2 | `phase-2-technical-design.md` | Architecture, data flow, technical decisions |
| 3 | `phase-3-test-plan.md` | Test cases, edge cases, validation scenarios |
| 4 | Implementation files | Code files as defined in Phase 2 |
| 5 | `phase-5-review.md` | Review notes, issues found, final validation |

### Workflow Continuity

- To resume work on a feature, read the latest phase artifact.
- To start the next phase, the previous phase artifact MUST exist and be approved.
- If an artifact is missing or incomplete, the AI MUST stop and request completion of the previous phase.

---

## Phase 1 — Feature Clarification & Scope Definition

### Objective
Define **WHAT** will be built and eliminate all ambiguity.

### AI Responsibilities
- Analyze the task description
- Identify ambiguities, inconsistencies, or missing information
- Ask clarifying questions when needed
- Interpret human answers given in natural language
- Produce a **Feature Specification** that clearly defines:
  - Scope
  - Definitions
  - Constraints
  - Explicit exclusions
- Identify whether any decision was made during clarification

### Mandatory Outputs
- A structured Feature Specification (Phase 1 Anchor)
- Updates to `docs/DECISIONS.md` if any decision was clarified or finalized

### Restrictions
- Do NOT propose architecture
- Do NOT define implementation details
- Do NOT write code
- Do NOT assume future features

---

## Phase 2 — Solution Design (Architecture & Flow)

### Objective
Define **HOW** the feature will be built.

### AI Responsibilities
- Propose a technical solution aligned with:
  - Existing architecture
  - Current codebase patterns
  - Documented conventions
- Identify:
  - Entities involved
  - Data flow
  - Responsibilities per layer
- Highlight trade-offs when relevant

### Mandatory Outputs
- Technical design description
- Identification of new architectural or technical decisions

### Restrictions
- Do NOT write production code
- Do NOT generate tests yet

---

## Phase 3 — Test Planning (Definition of Done)

### Objective
Define correctness before implementation.

### AI Responsibilities
- Define test cases that validate the feature
- Include:
  - Happy path
  - Edge cases
  - Validation rules
  - Failure scenarios
- Ensure that tests fully cover the Feature Specification

### Mandatory Outputs
- List of test cases / acceptance criteria
- Confirmation that the feature is fully specified

---

## Phase 4 — Implementation

### Objective
Implement the feature according to previous phases.

### AI Responsibilities
- Generate implementation code when requested
- Follow:
  - Approved design
  - Existing project patterns
  - Coding conventions
- Generate tests if explicitly requested

### Restrictions
- Do NOT change scope
- Do NOT introduce undocumented decisions

---

## Phase 5 — Review & Validation

### Objective
Ensure quality and correctness.

### AI Responsibilities
- Review implementation against:
  - Feature Specification
  - Test Plan
- Identify:
  - Missing cases
  - Logical errors
  - Architectural inconsistencies
- Suggest improvements without altering scope

---

## Decision Tracking Rule

Whenever the AI or human resolves any of the following, it MUST update `docs/DECISIONS.md`:

- Business rule definition
- Scope inclusion or exclusion
- Architectural choice
- Constraint assumption
- Terminology alignment

If no decision was made, the AI must explicitly state that no update is required.

---

## Enforcement

If a request violates this workflow, the AI MUST:
- Stop
- Explain what is missing
- Ask to resume from the correct phase
