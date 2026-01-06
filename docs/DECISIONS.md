# Architectural Decisions

Record of key decisions and explicit assumptions.

## Decisions

### D1: Actions Pattern over Service Classes

**Decision:** Use single-purpose Action classes instead of service classes.

**Rationale:**
- Each action does one thing (Single Responsibility)
- Easier to test
- Avoids bloated service classes
- Common pattern in Laravel community

**Example:** `LoginAction`, `CreateMedicationOfferingAction`, `SearchDrugAction`

---

### D2: Laravel Sanctum for API Authentication

**Decision:** Use Sanctum tokens instead of JWT or Passport.

**Rationale:**
- Built into Laravel, no extra packages
- Simple token-based auth fits mobile app needs
- Lightweight compared to OAuth (Passport)

---

### D3: Zustand over Redux for Mobile State

**Decision:** Use Zustand for state management in React Native.

**Rationale:**
- Minimal boilerplate
- No actions/reducers ceremony
- Sufficient for app complexity
- Better TypeScript inference

---

### D4: Form Requests for All Validation

**Decision:** All input validation happens in Form Request classes.

**Rationale:**
- Keeps controllers thin
- Reusable validation logic
- Built-in authorization hook
- Standardized error responses

---

### D5: Portuguese Validation Messages

**Decision:** All user-facing validation messages are in Portuguese.

**Rationale:**
- Target users are Brazilian
- Improves user experience
- Consistent with domain (CPF, CRM, ANVISA)

---

### D6: PostgreSQL Full-Text Search

**Decision:** Use PostgreSQL's built-in FTS for drug search instead of external services.

**Rationale:**
- No additional infrastructure
- Portuguese language support
- Sufficient for expected data volume
- Simpler deployment

---

### D7: Monorepo Structure

**Decision:** Keep backend and mobile in the same repository.

**Rationale:**
- Academic project scope
- Easier to manage for single developer
- Shared documentation
- Atomic commits across stack

---

## Assumptions

### A1: Single Developer/Small Team

The project assumes a small development team (1-2 people). Patterns are chosen for simplicity over enterprise scalability.

### A2: Brazilian Market Only

All localization, validation (CPF, CRM), and regulatory references (ANVISA) assume Brazilian market. Internationalization is not a requirement.

### A3: Drug Catalog is Static

The drugs table is populated from ANVISA data via import. Users do not create or edit drug entries. The import process is a batch operation.

### A4: No Payment Processing

The platform facilitates donations. There is no payment, subscription, or monetary transaction processing.

### A5: Mobile-First API

The API is designed primarily for mobile consumption. Admin features use Livewire (server-rendered) separately.

### A6: Trust Doctor Registration

CRM validation checks format only. External verification with medical councils is out of scope for the academic project.

### A7: Basic Address Handling

Addresses are stored as text. Geocoding, map integration, or proximity search are not implemented.

---

## Future Considerations

Items explicitly out of scope but documented for awareness:

1. **Notification system** - Push notifications for new offerings
2. **Reservation system** - Patients reserving medications
3. **Delivery tracking** - Tracking donation handoffs
4. **Analytics dashboard** - Usage statistics
5. **External CRM validation** - API integration with medical councils
6. **Multi-language support** - Internationalization

These may be added post-TCC if the project continues.
