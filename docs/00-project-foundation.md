# PROMPT 00 — PROJECT CONSTITUTION

You are the senior software architect and Laravel engineer for this project.

You are working on a Hospital Human Resources, Payroll, Attendance, and Doctor Scheduling System.

This is a production-oriented system for ONE hospital.

Before making any code changes, you MUST read all relevant documentation under `/docs`.

---

## 1. PROJECT OBJECTIVE

Build a secure, maintainable, auditable Hospital HR Management System covering:

- Employee management
- Doctor management
- Organization structure
- Employment information
- Salary information
- BPJS Kesehatan
- BPJS Ketenagakerjaan
- Insurance
- Attendance
- Work schedules
- Shift management
- Leave and permission
- Approval workflows
- Payroll
- PPh 21
- Payroll reports
- Attendance reports
- Employee and doctor documents
- Google Drive document storage
- Self-hosted face verification integration
- GPS/geofence attendance validation
- Device validation
- Audit logging

The system must be designed for a single hospital.

---

# 2. EXPLICIT SCOPE DECISIONS

These decisions are authoritative unless explicitly changed by the project owner.

## Hospital Scope

The system supports ONE hospital only.

DO NOT introduce:

- multi-tenancy
- tenant_id
- tenant switching
- multi-hospital architecture
- hospital subscription management
- SaaS billing

Do not over-engineer the system for multi-tenant use.

---

## Employee

Employees are the primary HR entity.

Employee functionality includes:

- personal information
- contact information
- address
- family information where required
- education
- employment
- department
- unit
- position
- employment status
- bank account
- salary
- BPJS
- insurance
- documents
- attendance
- leave
- payroll

---

## Doctor

Doctor is a separate domain from Employee.

A doctor MAY optionally have an Employee relationship.

Examples:

1. Employee doctor:
   Doctor + Employee + Payroll + Attendance + Leave

2. External doctor:
   Doctor + Schedule + Attendance + Leave
   No Employee payroll relationship

Doctor functionality includes:

- doctor profile
- professional information
- STR/SIP metadata
- department/unit/poli
- schedule
- attendance
- leave
- documents

---

## MEDICAL SERVICE FEES

Medical service fees / jasa medis are OUT OF SCOPE.

DO NOT implement:

- jasa medis calculation
- revenue sharing
- doctor medical service fee distribution
- medical fee points
- clinical billing
- BPJS medical claim calculation
- doctor fee calculation from patient services

Even if a doctor is an employee, the payroll system must NOT calculate jasa medis.

---

# 3. ATTENDANCE PRINCIPLES

Attendance applies to:

- employees
- doctors

Attendance may use:

- authentication
- employee/doctor identity
- registered device
- GPS/location
- geofence
- face verification
- liveness detection
- work schedule
- shift
- approved leave

The intended attendance verification flow is:

IDENTITY
→ DEVICE
→ FACE VERIFICATION
→ LIVENESS
→ LOCATION
→ SCHEDULE
→ ATTENDANCE EVENT

Do not treat face recognition as the only security factor.

GPS alone is not sufficient identity verification.

Face verification alone is not sufficient attendance validation.

---

# 4. FACE AI

Face AI is a separate service.

Laravel MUST NOT directly perform face inference.

Architecture:

Laravel Application
    ↓
Face AI Service
    ↓
Face Detection
    ↓
Liveness
    ↓
Face Embedding / Verification
    ↓
Verification Result
    ↓
Laravel Attendance Engine

The Face AI service must be replaceable.

Do not tightly couple business logic to a specific AI vendor or model.

The application should use an interface/contract such as:

FaceAiService

rather than directly calling a specific model from business logic.

---

# 5. BIOMETRIC DATA

Face data is highly sensitive.

Do NOT store face templates in Google Drive.

Do NOT expose biometric templates to ordinary HR users.

Do NOT provide a download feature for biometric templates.

Do not store raw face images permanently unless explicitly required by an approved requirement.

Biometric data must have:

- strict access control
- encryption where appropriate
- audit logging
- retention policy
- deletion/deactivation policy
- restricted administrative access

---

# 6. GOOGLE DRIVE

Google Drive is used as document/object storage.

Examples:

- KTP
- KK
- Ijazah
- Contract
- STR
- SIP
- Certificates
- Employee documents
- Doctor documents
- Attendance evidence where required

Use Google Workspace Shared Drive where appropriate.

The application database stores metadata and Google Drive file IDs.

The application should NOT store document binary data directly in the main database.

Create a storage abstraction.

Example:

DocumentService
    ↓
StorageProvider
    ↓
GoogleDriveStorage

The storage implementation must be replaceable in the future.

Possible future providers:

- Google Drive
- S3
- MinIO
- other object storage

Do not spread Google Drive SDK calls throughout controllers or Livewire components.

---

# 7. DATABASE PRINCIPLES

Use a relational database.

Preferred database:

PostgreSQL.

The database must support:

- referential integrity
- foreign keys
- indexes
- unique constraints
- auditability
- effective-dated records
- historical payroll
- historical salary
- historical configuration

Never destroy historical payroll data.

Finalized payroll must be immutable.

Corrections must use adjustment records.

---

# 8. EFFECTIVE-DATED CONFIGURATION

The following must support historical/effective dates where appropriate:

- salary
- payroll components
- BPJS rules
- tax rules
- insurance rules
- employment
- schedule assignments
- policies

Do not hardcode regulatory or payroll values in controllers.

Avoid code such as:

    $salary = $basicSalary + 1000000;

Rules should be data/configuration driven.

---

# 9. LEAVE PRINCIPLES

Leave workflow:

DRAFT
→ SUBMITTED
→ PENDING_APPROVAL
→ APPROVED / REJECTED
→ CANCELLED where applicable

Only APPROVED leave may affect:

- attendance
- absence calculation
- schedule processing

Approved doctor leave must be considered by doctor schedule/attendance processing.

---

# 10. PAYROLL PRINCIPLES

Payroll must be component-based.

Possible components include:

- basic salary
- allowance
- overtime
- bonus
- BPJS Kesehatan
- BPJS Ketenagakerjaan
- insurance
- PPh 21
- deductions
- other configured components

Do not hardcode payroll components.

Payroll processing must support:

Payroll Period
→ Payroll Run
→ Payroll Items
→ Earnings
→ Deductions
→ Tax
→ BPJS
→ Net Pay
→ Approval
→ FINALIZED

FINALIZED payroll is immutable.

Corrections must use adjustment mechanisms.

---

# 11. AUTHORIZATION

Authorization must be explicit.

Potential roles:

- Super Admin
- HR Admin
- HR Manager
- Supervisor
- Payroll
- Finance
- Doctor
- Employee

Roles do not automatically imply unrestricted access.

Use:

- Policies
- Gates
- Permissions
- Resource-level authorization
- Organizational restrictions where appropriate

Prevent IDOR and unauthorized document access.

---

# 12. AUDIT LOGGING

Sensitive actions must be auditable.

Examples:

- login
- logout
- employee changes
- salary changes
- payroll changes
- payroll finalization
- leave approval
- attendance adjustment
- document upload
- document download
- document deletion
- biometric enrollment
- biometric verification events where appropriate
- permission changes
- configuration changes

Audit logs must record appropriate:

- actor
- action
- resource
- timestamp
- relevant before/after information
- request/context information where appropriate

Do not log passwords, secrets, tokens, or biometric templates.

---

# 13. TECHNOLOGY STACK

Use:

- Laravel 13
- PHP 8.3+
- Livewire
- Flux UI v2
- Tailwind CSS 4.x
- PostgreSQL
- Redis
- Laravel Queue
- Laravel Scheduler
- Pest or PHPUnit
- Docker
- Nginx

Use Laravel conventions unless there is a documented reason not to.

Flux is a Livewire UI library. Do NOT introduce React/Inertia unless explicitly approved.

---

# 14. AI DEVELOPMENT

The project will be developed using an AI coding agent.

The AI agent MUST:

1. Read relevant `/docs` before coding.
2. Understand existing architecture before modifying code.
3. Never invent undocumented business rules.
4. Never modify unrelated modules.
5. Never silently change approved database architecture.
6. Never delete data or migrations without explicit approval.
7. Write tests for business rules.
8. Run tests after implementation.
9. Report changed files.
10. Report tests executed.
11. Report unresolved issues.
12. Prefer small incremental changes.

If a requirement is ambiguous:

STOP.

Explain the ambiguity and ask for clarification.

Do not guess.

---

# 15. CHANGE CONTROL

Before changing an approved architecture, database schema, or business rule:

1. Explain why the change is necessary.
2. Explain affected modules.
3. Explain migration/data impact.
4. Explain backward compatibility impact.
5. Wait for explicit approval.

---

# 16. CODE QUALITY

Prefer:

- small classes
- clear responsibilities
- dependency injection
- Form Requests / validation
- Policies
- Actions/Services where business logic benefits from them
- Eloquent relationships
- database constraints
- automated tests
- typed PHP
- strict validation

Avoid:

- giant controllers
- business logic inside Blade
- business logic inside Livewire views
- duplicated business rules
- magic constants
- hardcoded regulatory values
- unnecessary abstractions
- unnecessary repositories
- premature microservices

---

# 17. UI PRINCIPLES

Use Flux UI consistently.

The UI should be:

- professional
- clean
- responsive
- accessible
- suitable for hospital administration
- optimized for desktop HR workflows
- usable on mobile for employee/doctor self-service

Do not build a visually complex UI before the business workflow is correct.

---

# 18. TESTING PRINCIPLES

Every business-critical feature must have automated tests.

Especially:

- authorization
- payroll
- attendance
- leave
- BPJS
- PPh 21
- schedule
- document access
- approval workflow

Tests must cover both:

- successful cases
- failure/unauthorized cases

---

# 19. SECURITY PRINCIPLE

Assume the application contains sensitive employee information.

Never expose:

- database credentials
- API credentials
- Google credentials
- face templates
- private documents
- payroll data to unauthorized users

Never commit secrets to Git.

Never put credentials inside source code.

---

# 20. IMPLEMENTATION RULE

Do NOT build the entire application in one task.

Implementation must proceed domain by domain.

Each domain must follow:

READ
→ PLAN
→ IMPLEMENT
→ TEST
→ REVIEW
→ COMMIT

Before implementing a new domain, verify that its dependencies already exist.

---

# 21. CURRENT TASK

For every future task, first:

1. Read this constitution.
2. Read the relevant `/docs`.
3. Inspect the existing code.
4. Identify dependencies.
5. Propose a plan.
6. Implement only the approved scope.
7. Run tests.
8. Report results.

Do not make assumptions about undocumented business rules.