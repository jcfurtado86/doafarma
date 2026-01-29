# API Reference

Complete documentation of the DoaFarma REST API.

---

## Base URL

```
Development: http://localhost:8000/api
Production:  https://api.doafarma.com/api
```

---

## Authentication

DoaFarma uses Laravel Sanctum for token-based authentication with refresh tokens.

### Token Types

| Token | Expiration | Ability | Usage |
|-------|-----------|---------|-------|
| Access Token | 1 hour | `access` | API requests |
| Refresh Token | 30 days | `refresh` | Get new access token |

### Login (Getting Tokens)

```http
POST /v1/auth/login
Content-Type: application/json

{
    "email": "doctor@example.com",
    "password": "password123",
    "device_name": "iPhone 15"
}
```

**Response (200):**
```json
{
    "data": {
        "user": {
            "id": 1,
            "name": "Dr. João Silva",
            "email": "doctor@example.com",
            "role": "doctor",
            "status": "approved"
        },
        "access_token": "1|abc123...",
        "refresh_token": "2|xyz789...",
        "expires_in": 3600,
        "refresh_expires_in": 2592000
    },
    "message": "Login realizado com sucesso"
}
```

### Using the Access Token

Include the access token in the `Authorization` header:

```http
Authorization: Bearer 1|abc123...
```

### Refreshing the Access Token

When the access token expires, use the refresh token to get a new one:

```http
POST /v1/auth/refresh
Authorization: Bearer 2|xyz789...
```

**Response (200):**
```json
{
    "data": {
        "access_token": "3|newtoken...",
        "expires_in": 3600
    },
    "message": "Token renovado com sucesso"
}
```

### Logout

Revokes all tokens for the current device:

```http
POST /v1/auth/logout
Authorization: Bearer 1|abc123...
```

**Response (200):**
```json
{
    "message": "Logout realizado com sucesso"
}
```

### Token Lifecycle

- **Access token expires after 1 hour** - use refresh token to renew
- **Refresh token expires after 30 days** - user must login again
- Logout revokes all tokens for the device (access + refresh)
- Multiple devices allowed (each device has its own token pair)
- Rate limiting: 5 login attempts per minute per email+IP

---

## Common Headers

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

---

## Error Responses

### Standard Error Format

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "O e-mail é obrigatório."
        ]
    }
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 204 | No Content (success, no body) |
| 401 | Unauthorized (invalid/missing token) |
| 403 | Forbidden (not allowed) |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests (rate limit) |
| 500 | Server Error |

---

## Rate Limiting

- Login: 5 attempts per minute per IP
- API: 60 requests per minute per user
- Exceeded: Returns 429 with `Retry-After` header

---

## Endpoints

### Authentication

#### POST /login

Authenticate a user and receive an access token.

**Request:**
```json
{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "Mobile App"
}
```

**Response (200):**
```json
{
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "João Silva",
        "email": "user@example.com",
        "role": "doctor",
        "status": "approved"
    }
}
```

**Errors:**
- 401: Invalid credentials
- 422: Validation errors
- 429: Too many attempts

---

#### POST /register/doctor

Register a new doctor account.

**Request:**
```json
{
    "name": "Dr. João Silva",
    "email": "joao@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone_number": "11999998888",
    "crm": "123456",
    "crm_uf": "SP",
    "addresses": [
        {
            "location_name": "Consultório",
            "full_address": "Rua das Flores, 123, São Paulo, SP",
            "complement": "Sala 101",
            "cep": "01234567"
        }
    ],
    "terms_accepted": true
}
```

**Response (201):**
```json
{
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "Dr. João Silva",
        "email": "joao@example.com",
        "role": "doctor",
        "status": "pending"
    }
}
```

**Validation Rules:**
- `name`: required, max 255 chars
- `email`: required, valid email, unique
- `password`: required, min 8 chars, confirmed
- `phone_number`: required, 10-11 digits, unique
- `crm`: required, 6 digits, unique
- `crm_uf`: required, 2 letters (valid Brazilian state)
- `addresses`: required, min 1 item
- `terms_accepted`: required, must be true

---

#### POST /register/receptor

Register a new receptor account.

**Request:**
```json
{
    "name": "Maria Santos",
    "email": "maria@example.com",
    "cpf": "12345678901",
    "password": "password123",
    "password_confirmation": "password123",
    "phone_number": "11999997777",
    "terms_accepted": true
}
```

**Response (201):**
```json
{
    "token": "1|abc123...",
    "user": {
        "id": 2,
        "name": "Maria Santos",
        "email": "maria@example.com",
        "role": "receptor",
        "status": "pending"
    }
}
```

**Validation Rules:**
- `cpf`: required, 11 digits, valid checksum, unique

---

### Drugs

#### GET /v1/drugs

List all drugs in the catalog.

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "product_name": "Dipirona Sódica",
            "substance": "DIPIRONA SÓDICA",
            "laboratory": "EMS S/A",
            "registration_number": "1234567890123",
            "presentation": "500 MG COM CT BL AL PLAS INC X 20",
            "stripe_color": "yellow"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 100,
        "per_page": 15,
        "total": 1500
    }
}
```

---

#### GET /v1/drugs/search

Search drugs by name, substance, or laboratory.

**Query Parameters:**
- `q`: Search query (required, min 2 chars)

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "product_name": "Dipirona Sódica",
            "substance": "DIPIRONA SÓDICA",
            "laboratory": "EMS S/A"
        }
    ]
}
```

**Notes:**
- Uses PostgreSQL full-text search with Portuguese stemming
- Searches across product_name, substance, and laboratory
- Returns max 50 results

---

### Medication Offerings

#### GET /v1/medication-offerings

List medication offerings. Doctors see their own; receptors see all available.

**Query Parameters:**
- `page`: Page number
- `per_page`: Items per page

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "lot_number": "LOT123",
            "expires_at": "2025-12-31",
            "quantity": 50,
            "status": "available",
            "drug": {
                "id": 1,
                "product_name": "Dipirona Sódica",
                "substance": "DIPIRONA SÓDICA"
            },
            "doctor": {
                "id": 1,
                "user": {
                    "name": "Dr. João Silva"
                }
            },
            "created_at": "2024-01-15T10:30:00Z"
        }
    ]
}
```

---

#### GET /v1/medication-offerings/search

Search available offerings by drug name or substance.

**Query Parameters:**
- `q`: Search query (required)

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "quantity": 50,
            "status": "available",
            "drug": {
                "product_name": "Dipirona Sódica"
            },
            "doctor": {
                "user": {
                    "name": "Dr. João Silva"
                }
            }
        }
    ]
}
```

---

#### GET /v1/medication-offerings/{id}

Get details of a specific offering.

**Response (200):**
```json
{
    "data": {
        "id": 1,
        "lot_number": "LOT123",
        "expires_at": "2025-12-31",
        "quantity": 50,
        "status": "available",
        "drug": {
            "id": 1,
            "product_name": "Dipirona Sódica",
            "substance": "DIPIRONA SÓDICA",
            "laboratory": "EMS S/A",
            "presentation": "500 MG COM CT BL AL PLAS INC X 20"
        },
        "doctor": {
            "id": 1,
            "crm": "123456",
            "crm_uf": "SP",
            "user": {
                "name": "Dr. João Silva"
            },
            "addresses": [
                {
                    "location_name": "Consultório",
                    "full_address": "Rua das Flores, 123"
                }
            ]
        }
    }
}
```

---

#### POST /v1/medication-offerings

Create a new medication offering. **Doctor only.**

**Request:**
```json
{
    "drug_id": 1,
    "lot_number": "LOT123",
    "expires_at": "2025-12-31",
    "quantity": 50
}
```

**Response (201):**
```json
{
    "data": {
        "id": 1,
        "lot_number": "LOT123",
        "expires_at": "2025-12-31",
        "quantity": 50,
        "status": "available"
    }
}
```

**Validation Rules:**
- `drug_id`: required, must exist in drugs table
- `lot_number`: required, max 50 chars
- `expires_at`: required, date, after today, before +10 years
- `quantity`: required, integer, 1-100000

---

#### PUT /v1/medication-offerings/{id}

Update an offering. **Doctor only, own offerings.**

**Request:**
```json
{
    "lot_number": "LOT456",
    "expires_at": "2026-06-30",
    "quantity": 30
}
```

**Response (200):**
```json
{
    "data": {
        "id": 1,
        "lot_number": "LOT456",
        "expires_at": "2026-06-30",
        "quantity": 30,
        "status": "available"
    }
}
```

---

#### DELETE /v1/medication-offerings/{id}

Delete an offering. **Doctor only, own offerings.**

**Response:** 204 No Content

---

### Medication Requests

#### GET /v1/medication-requests

List requests created by the current receptor.

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "status": "pending",
            "created_at": "2024-01-15T10:30:00Z",
            "medication_offering": {
                "id": 1,
                "drug": {
                    "product_name": "Dipirona Sódica"
                }
            }
        }
    ]
}
```

---

#### GET /v1/medication-requests/received

List requests received by the current doctor.

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "status": "pending",
            "created_at": "2024-01-15T10:30:00Z",
            "receptor": {
                "name": "Maria Santos"
            },
            "medication_offering": {
                "id": 1,
                "drug": {
                    "product_name": "Dipirona Sódica"
                }
            }
        }
    ]
}
```

---

#### POST /v1/medication-requests

Create a request for a medication offering. **Receptor only.**

**Request:**
```json
{
    "medication_offering_id": 1
}
```

**Response (201):**
```json
{
    "data": {
        "id": 1,
        "status": "pending",
        "medication_offering": {
            "id": 1,
            "status": "reserved"
        }
    }
}
```

**Errors:**
- 422: Offering not available
- 422: Duplicate pending request
- 422: Cannot request own offering

---

#### PATCH /v1/medication-requests/{id}/confirm

Confirm a request. **Doctor only, own offerings.**

**Response (200):**
```json
{
    "data": {
        "id": 1,
        "status": "confirmed"
    }
}
```

---

#### PATCH /v1/medication-requests/{id}/reject

Reject a request. **Doctor only, own offerings.**

**Response (200):**
```json
{
    "data": {
        "id": 1,
        "status": "rejected"
    }
}
```

**Side effect:** Offering status returns to 'available'

---

### Medication Appointments

#### GET /v1/medication-appointments

List pending/confirmed appointments for current user.

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "scheduled_date": "2024-02-15",
            "scheduled_time": "14:00",
            "status": "proposed",
            "proposed_by": "receptor",
            "address": {
                "location_name": "Consultório",
                "full_address": "Rua das Flores, 123"
            },
            "medication_request": {
                "id": 1,
                "medication_offering": {
                    "drug": {
                        "product_name": "Dipirona Sódica"
                    }
                }
            }
        }
    ]
}
```

---

#### GET /v1/medication-appointments/history

List completed appointments for current receptor.

---

#### GET /v1/medication-appointments/doctor-history

List completed appointments for current doctor.

---

#### GET /v1/medication-appointments/received

List appointments where current user needs to respond.

---

#### POST /v1/medication-appointments

Create an appointment proposal.

**Request:**
```json
{
    "medication_request_id": 1,
    "scheduled_date": "2024-02-15",
    "scheduled_time": "14:00"
}
```

**Response (201):**
```json
{
    "data": {
        "id": 1,
        "scheduled_date": "2024-02-15",
        "scheduled_time": "14:00",
        "status": "proposed",
        "proposed_by": "receptor"
    }
}
```

---

#### PATCH /v1/medication-appointments/{id}/accept

Accept the current proposal.

**Response (200):**
```json
{
    "data": {
        "id": 1,
        "status": "confirmed",
        "doctor_confirmed": true,
        "receptor_confirmed": true
    }
}
```

---

#### PATCH /v1/medication-appointments/{id}/counter-propose

Propose a different time/location.

**Request:**
```json
{
    "scheduled_date": "2024-02-20",
    "scheduled_time": "10:00",
    "address_id": 2
}
```

**Response (200):**
```json
{
    "data": {
        "id": 1,
        "scheduled_date": "2024-02-20",
        "scheduled_time": "10:00",
        "status": "proposed",
        "proposed_by": "doctor"
    }
}
```

---

#### PATCH /v1/medication-appointments/{id}/confirm-delivery-receptor

Receptor confirms they received the medication.

**Response (200):**
```json
{
    "data": {
        "id": 1,
        "status": "confirmed",
        "receptor_confirmed": true
    }
}
```

---

#### PATCH /v1/medication-appointments/{id}/confirm-delivery-doctor

Doctor confirms they delivered the medication.

**Response (200):**
```json
{
    "data": {
        "id": 1,
        "status": "completed",
        "doctor_confirmed": true,
        "receptor_confirmed": true
    }
}
```

**Note:** When both confirm, status becomes 'completed'

---

### Doctor Ratings

#### GET /v1/doctor-ratings/my-ratings

List ratings submitted by current receptor.

---

#### GET /v1/doctor-ratings/doctor/{doctor}

List all ratings for a specific doctor.

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "rating": 5,
            "comment": "Excelente atendimento!",
            "created_at": "2024-02-16T10:00:00Z"
        }
    ],
    "meta": {
        "average_rating": 4.8,
        "total_ratings": 15
    }
}
```

---

#### GET /v1/doctor-ratings/appointment/{medicationAppointment}

Get rating for a specific appointment.

---

#### POST /v1/doctor-ratings/{medicationAppointment}

Create a rating for a completed appointment. **Receptor only.**

**Request:**
```json
{
    "rating": 5,
    "comment": "Muito atencioso, recomendo!"
}
```

**Response (201):**
```json
{
    "data": {
        "id": 1,
        "rating": 5,
        "comment": "Muito atencioso, recomendo!",
        "doctor_id": 1,
        "receptor_id": 2
    }
}
```

**Validation:**
- `rating`: required, integer, 1-5
- `comment`: optional, max 1000 chars

---

#### PATCH /v1/doctor-ratings/{id}

Update a rating (comment only). **Receptor only, own ratings.**

**Request:**
```json
{
    "comment": "Updated comment"
}
```

---

### Push Tokens

#### POST /v1/push-tokens

Register a push notification token.

**Request:**
```json
{
    "token": "ExponentPushToken[xxx]",
    "device_type": "ios"
}
```

**Response:** 201 Created

---

#### DELETE /v1/push-tokens

Remove current device's push token.

**Response:** 204 No Content

---

## Pagination

Paginated endpoints return a `meta` object:

```json
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "per_page": 15,
        "to": 15,
        "total": 150
    },
    "links": {
        "first": "http://api.example.com/resource?page=1",
        "last": "http://api.example.com/resource?page=10",
        "prev": null,
        "next": "http://api.example.com/resource?page=2"
    }
}
```

---

## Filtering and Sorting

Most list endpoints support:

- `sort`: Field to sort by (prefix with `-` for descending)
- `filter[field]`: Filter by field value

Example:
```
GET /v1/medication-offerings?sort=-created_at&filter[status]=available
```
