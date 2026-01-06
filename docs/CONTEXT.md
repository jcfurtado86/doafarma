# Project Context

## Overview

**DoaFarma** is a medication donation platform that connects doctors who have excess medications with patients (receptors) who need them. This is an academic project (TCC - undergraduate thesis).

## Domain Entities

### User
Base entity for authentication. Has a `role` that defines the user type.

| Field | Description |
|-------|-------------|
| name | Full name |
| email | Unique, used for login |
| cpf | Brazilian tax ID (receptors only) |
| phone_number | 10-11 digits |
| role | `doctor` or `receptor` |
| terms_accepted | Must accept terms to register |

### Doctor
Medical professional who donates medications. Extends User.

| Field | Description |
|-------|-------------|
| crm | Medical license number (6 digits) |
| crm_uf | State code (2 letters, e.g., SP, RJ) |
| addresses | At least one address required |

### Drug
Pharmaceutical products registered in ANVISA (Brazilian FDA).

| Field | Description |
|-------|-------------|
| product_name | Commercial name |
| substance | Active ingredient |
| laboratory | Manufacturer |
| registration_number | ANVISA registration |
| presentation | Dosage form and quantity |
| stripe_color | Regulatory classification |

### MedicationOffering
A doctor's offer to donate specific medications.

| Field | Description |
|-------|-------------|
| doctor_id | Owner of the offering |
| drug_id | Reference to drug catalog |
| lot_number | Batch identifier |
| expires_at | Expiration date (must be future) |
| quantity | Units available (1-100,000) |

### Address
Physical location associated with a user (primarily doctors).

| Field | Description |
|-------|-------------|
| location_name | Identifier (e.g., "Consultório") |
| full_address | Complete address |
| complement | Optional additional info |
| cep | Brazilian postal code (8 digits) |

## User Flows

### Doctor Registration
1. Provide personal info (name, email, phone)
2. Enter CRM credentials (number + state)
3. Add at least one address
4. Accept terms of service
5. Receive auth token

### Receptor Registration
1. Provide personal info (name, email, CPF, phone)
2. Accept terms of service
3. Receive auth token

### Medication Offering (Doctor)
1. Search drug catalog
2. Select drug
3. Enter lot, expiration, quantity
4. Submit offering

## Business Rules

1. Only doctors can create medication offerings
2. Doctors can only edit/delete their own offerings
3. Expiration date must be in the future (max 10 years)
4. CRM must be unique per doctor
5. CPF must be valid and unique per receptor
6. Email and phone must be unique across all users
