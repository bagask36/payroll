# PROMPT 03 — DATABASE ARCHITECTURE AND ERD

You are acting as a Senior Database Architect specializing in PostgreSQL and Laravel.

Read completely:

- `/docs/00-project-constitution.md`
- `/docs/01-requirements.md`
- `/docs/02-architecture.md`

Do NOT create Laravel migrations yet.

Do NOT modify application code.

Your task is to design the complete relational database model.

---

# 1. DATABASE GOAL

The database must support:

- one hospital
- employees
- doctors
- organization
- employment
- salary history
- BPJS
- insurance
- schedules
- shifts
- attendance
- leave
- approvals
- payroll
- PPh 21
- documents
- audit logs
- system configuration

The schema must preserve historical records.

---

# 2. DESIGN PRINCIPLES

Use PostgreSQL.

Prioritize:

- referential integrity
- normalized data
- appropriate denormalization only when justified
- foreign keys
- unique constraints
- check constraints where useful
- indexes
- effective dates
- immutable financial records
- auditability

Do not introduce multi-tenancy.

Do not add `tenant_id`.

---

# 3. IDENTIFIERS

Choose and document whether the system uses:

- UUID
- ULID
- bigint

Use one consistent strategy unless there is a strong domain-specific reason to differ.

Document the decision.

---

# 4. CORE TABLES

Design tables for:

## Authentication

- users
- roles
- permissions
- role_user
- permission_role

## Organization

- hospitals if needed only as a single organizational root
- departments
- units
- positions
- work_locations
- holidays

Do not design multi-hospital support.

---

# 5. EMPLOYEE TABLES

Design:

- employees
- employee_employments
- employee_addresses where appropriate
- employee_contacts where appropriate
- employee_bank_accounts
- employee_salaries
- employee_bpjs
- employee_insurances

Salary must support history.

Employment must support history.

Do not overwrite historical salary records.

---

# 6. DOCTOR TABLES

Design:

- doctors
- doctor_documents if appropriate
- doctor_schedules or schedule assignment relationship

Support:

Doctor
→ optional Employee

Avoid duplicating employee information unnecessarily.

Do NOT create medical service fee tables.

---

# 7. DOCUMENT TABLES

Create a generic document model capable of relating documents to:

- employee
- doctor
- other approved entities

The document table should store metadata.

At minimum consider:

- document type
- original filename
- MIME type
- file size
- storage provider
- storage key
- Google Drive file ID
- folder ID where appropriate
- uploader
- timestamps
- deletion state

Do not store face templates here.

---

# 8. SCHEDULE TABLES

Design:

- shifts
- work_schedules
- schedule_assignments
- doctor_schedule_assignments if required
- holidays

Support:

- normal shifts
- overnight shifts
- recurring schedules if required
- effective dates
- schedule conflicts

---

# 9. ATTENDANCE TABLES

Separate:

## Raw attendance evidence

from:

## Processed attendance

from:

## Manual adjustments

Design appropriate tables such as:

- attendance_sessions
- attendance_events
- attendance_records
- attendance_adjustments
- attendance_evidences

Do not blindly use these exact names if another normalized design is better.

Document the reasoning.

Attendance should support:

- check-in
- check-out
- late
- early leave
- absence
- overtime
- manual correction
- GPS metadata
- device metadata
- face verification metadata
- liveness result
- evidence reference

Do not store raw face templates in attendance tables.

---

# 10. LEAVE TABLES

Design:

- leave_types
- leave_policies
- leave_balances
- leave_requests
- leave_request_days
- leave_approvals

Support:

- workday-aware calculation
- weekends
- holidays
- partial days if required
- approved leave affecting attendance

---

# 11. APPROVAL TABLES

Design configurable workflows.

Possible tables:

- approval_workflows
- approval_steps
- approval_transactions

Approval must support different workflows for:

- employee leave
- doctor leave
- attendance adjustment
- payroll
- other approved processes

Do not hardcode a single approval chain.

---

# 12. PAYROLL TABLES

Design:

- payroll_periods
- payroll_runs
- payroll_items
- payroll_components
- payroll_component_rules if needed
- payroll_adjustments

Support:

- earnings
- deductions
- overtime
- bonuses
- BPJS
- insurance
- PPh21

Finalized payroll must remain immutable.

---

# 13. BPJS TABLES

Design configurable BPJS structures.

Possible:

- bpjs_programs
- bpjs_rules
- employee_bpjs

Rules must support:

- employee contribution
- employer contribution
- effective date
- calculation basis
- ceilings
- program type

Do not hardcode current contribution percentages.

---

# 14. TAX TABLES

Design:

- tax_profiles
- tax_rules
- employee_tax_profiles where needed

Support effective dating.

Do not hardcode PPh 21 rules directly into application logic.

---

# 15. INSURANCE

Design:

- insurance_providers
- insurance_plans
- employee_insurances
- insurance_rules if required

Support historical coverage where necessary.

---

# 16. AUDIT

Design:

- audit_logs

Audit logs should be capable of recording:

- actor
- action
- resource type
- resource ID
- timestamp
- relevant changes
- request metadata where appropriate

Do not store secrets or biometric templates.

---

# 17. SYSTEM CONFIGURATION

Design configuration storage only where appropriate.

Avoid turning every application value into a database setting.

Separate:

- business configuration
- application configuration
- secrets

Secrets must remain outside normal database configuration.

---

# 18. RELATIONSHIPS

For every relationship document:

- one-to-one
- one-to-many
- many-to-many
- polymorphic where justified

Explain why polymorphic relationships are used.

Avoid polymorphic relationships when a normal foreign key is safer and clearer.

---

# 19. INDEXING

For every major table identify:

- primary key
- unique indexes
- foreign key indexes
- common query indexes
- composite indexes where justified

Consider queries involving:

- employee number
- doctor number
- attendance date
- schedule date
- payroll period
- leave status
- document owner
- approval status

---

# 20. DATA INTEGRITY

Define database constraints for important rules.

Examples:

- unique employee number
- unique doctor number
- valid employment dates
- valid effective date ranges
- non-negative monetary values where appropriate
- valid payroll states

Do not rely solely on frontend validation.

---

# 21. STATE MACHINES

Define states for:

### Leave

DRAFT
SUBMITTED
PENDING_APPROVAL
APPROVED
REJECTED
CANCELLED

### Payroll

DRAFT
PROCESSING
CALCULATED
PENDING_APPROVAL
APPROVED
FINALIZED
VOID where explicitly justified

### Attendance Session

CREATED
VALIDATING
VERIFIED
RECORDED
FAILED
EXPIRED

Document allowed transitions.

---

# 22. MONEY

Define monetary data types.

Prefer PostgreSQL numeric/decimal for financial values.

Do not use floating-point types for salary or monetary calculations.

Define:

- precision
- scale
- rounding strategy

Do not invent regulatory rounding rules. Mark them for confirmation if not documented.

---

# 23. ERD

Create a Mermaid ERD.

The ERD must clearly show:

Employee
Doctor
Organization
Schedule
Attendance
Leave
Payroll
BPJS
Tax
Documents
Approval
Audit

---

# 24. DATA DICTIONARY

For every table provide:

- table
- purpose
- column
- type
- nullable
- default
- description
- constraints

---

# 25. MIGRATION PLAN

Do not create migrations yet.

Instead provide the recommended migration order.

Example:

1. authentication
2. organization
3. employees
4. doctors
5. schedules
6. documents
7. leave
8. attendance
9. payroll
10. configuration
11. audit

Adjust the order based on actual dependencies.

---

# 26. OUTPUT

Create:

`/docs/03-database.md`

Do not modify application code.

Do not generate Laravel migration files.

At the end report:

- number of tables
- major relationships
- major indexes
- immutable entities
- effective-dated entities
- unresolved database decisions
- migration order