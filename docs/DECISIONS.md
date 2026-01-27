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

### D8: Partial Index for Active Medication Requests

**Decision:** Use PostgreSQL partial unique index to ensure only one pending request per offering.

**Rationale:**
- Prevents race conditions at database level
- More reliable than application-level checks alone
- Allows historical rejected requests to coexist with new pending requests
- SQLite fallback uses application-level validation with DB transaction for testing

---

### D9: Explicit Status on MedicationOffering

**Decision:** Add explicit `status` field to MedicationOffering rather than deriving from requests.

**Rationale:**
- Simpler queries for available offerings
- Clear state management
- Easier to extend with future statuses (e.g., 'completed')
- Avoids complex joins for basic listing operations

---

### D10: Receptor via User Model

**Decision:** Store `receptor_id` pointing to `users.id` rather than creating a separate `Receptor` model.

**Rationale:**
- Simpler architecture (users already have `role` field)
- Consistent with existing pattern (doctors have separate table for CRM data)
- Receptors don't have additional required fields beyond User
- Single authentication flow for all user types

---

### D11: Filament for Admin Panel

**Decision:** Use Filament v3 for the admin interface instead of custom API endpoints.

**Rationale:**
- Complete admin panel with minimal code
- Built-in CRUD, filters, bulk actions, and notifications
- Handles authentication and authorization
- Consistent UI/UX out of the box
- Separates admin interface from mobile API
- Active community and good Laravel integration

**Note:** API endpoints remain for mobile app; Filament handles web admin only.

---

### D12: User Status Workflow for Registration Validation

**Decision:** Implement a pending/approved/rejected workflow for user registration.

**Rationale:**
- Security: prevents unauthorized access until admin verifies user
- Audit trail: tracks who approved/rejected and when
- Flexibility: allows for future automation of approval process
- Compliance: ensures only verified doctors can offer medications

**Flow:**
1. User registers → status = `pending`
2. User attempts API access → blocked with 403 if not approved
3. Admin reviews and approves/rejects via Filament panel
4. Approved users gain full API access

---

### D13: Spatie Activity Log for Platform Monitoring

**Decision:** Use spatie/laravel-activitylog package for tracking all system activities.

**Rationale:**
- Battle-tested, widely used in Laravel community
- Automatic tracking via model traits
- Tracks before/after values for updates
- Identifies who made each change (causer)
- Minimal configuration needed
- Integrates seamlessly with Filament admin panel

**Implementation:**
- Models with `LogsActivity` trait: User, MedicationOffering, MedicationRequest, MedicationAppointment
- Custom events for user approval/rejection actions
- ActivityLogResource in Filament for admin viewing
- Dashboard widgets for activity statistics and recent actions
- Filters by entity type, action, date range, and user

**Benefits:**
- Admin can monitor all platform activity
- Audit trail for security and compliance
- Helps identify misuse or suspicious patterns
- Historical record of all changes

---

## Future Considerations

Items explicitly out of scope but documented for awareness:

1. **Notification system** - Push notifications for new offerings
2. ~~**Reservation system** - Patients reserving medications~~ (Implemented)
3. **Delivery tracking** - Tracking donation handoffs
4. **Analytics dashboard** - Usage statistics
5. **External CRM validation** - API integration with medical councils
6. **Multi-language support** - Internationalization

These may be added post-TCC if the project continues.
