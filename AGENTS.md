# AGENTS.md

## Hospital HR Management System

This file contains the mandatory instructions for AI coding agents working on this repository.

The system is a production-oriented Human Resources, Payroll, Attendance, Leave, and Doctor Scheduling System for ONE hospital.

---

# 1. MANDATORY PROJECT CONTEXT

Before modifying code, always read the relevant documentation under:

/docs

The project documentation includes:

- `/docs/00-project-constitution.md`
- `/docs/01-requirements.md`
- `/docs/02-architecture.md`
- `/docs/03-database.md`
- `/docs/04-security.md`
- `/docs/05-roles-permissions.md`

Do not assume undocumented business rules.

If documentation conflicts with the existing code, STOP and report the conflict before making changes.

If requirements are ambiguous, STOP and ask for clarification.

Do not invent business rules.

---

# 2. PROJECT SCOPE

This is a SINGLE-HOSPITAL system.

The system is NOT multi-tenant.

DO NOT introduce:

- tenant_id
- tenant switching
- multi-hospital support
- SaaS subscription architecture
- tenant billing

unless explicitly approved by the project owner.

---

# 3. TECHNOLOGY STACK

The application uses:

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

Use the versions actually installed in the project.

Do not upgrade framework or major dependencies unless explicitly requested.

Do not introduce React, Inertia, Vue, or another frontend framework unless explicitly approved.

Flux is the primary UI component library.

Follow official Laravel, Livewire, Flux, and Tailwind conventions.

If Laravel Boost is installed, use its documentation search and project inspection capabilities when appropriate.

---

# 4. SOURCE OF TRUTH

Use the following priority when determining project behavior:

1. Explicit instruction from the project owner
2. Approved `/docs` documentation
3. Existing tested application behavior
4. Laravel/package official documentation
5. Reasonable implementation convention

Never override an explicit project requirement with a generic AI assumption.

---

# 5. DEVELOPMENT WORKFLOW

Every feature must follow:

READ
→ UNDERSTAND
→ PLAN
→ IMPLEMENT
→ TEST
→ REVIEW
→ REPORT

Before coding:

1. Read relevant `/docs`.
2. Inspect the existing implementation.
3. Identify dependencies.
4. Identify affected files.
5. Explain the implementation plan.
6. Implement only the approved scope.

After coding:

1. Run relevant tests.
2. Run formatting/static checks where applicable.
3. Review changed files.
4. Check authorization.
5. Check validation.
6. Check database integrity.
7. Report the result.

---

# 6. NEVER IMPLEMENT THE ENTIRE SYSTEM AT ONCE

Do NOT attempt to build the complete Hospital HR System from one prompt.

Implement one bounded domain or feature at a time.

Examples:

- Authentication
- Organization
- Employee
- Doctor
- Scheduling
- Attendance
- Leave
- Payroll
- Documents
- Face AI integration

Each feature must be independently reviewable.

---

# 7. CHANGE CONTROL

Do not silently change:

- database architecture
- business rules
- authorization model
- payroll rules
- attendance rules
- document architecture
- Face AI architecture
- Google Drive architecture

If a change is necessary:

1. Explain the reason.
2. Identify affected modules.
3. Identify database impact.
4. Identify security impact.
5. Identify backward compatibility impact.
6. Ask for approval if the change affects an approved architecture or requirement.

---

# 8. DATABASE RULES

PostgreSQL is the primary database.

Use:

- foreign keys
- unique constraints
- appropriate indexes
- check constraints where useful
- transactions for multi-step business operations
- appropriate numeric types for money

Do NOT use floating-point types for financial values.

Do NOT remove historical records simply to simplify implementation.

Preserve historical:

- employment
- salary
- payroll
- attendance adjustments
- configuration
- approval records

Finalized payroll is immutable.

Corrections must use adjustment mechanisms.

---

# 9. MIGRATION RULES

Every schema change must use a Laravel migration.

Never manually modify the production database schema without an approved migration.

Before modifying an existing table:

1. Inspect the current migration history.
2. Inspect the actual database schema if available.
3. Determine whether existing data is affected.
4. Consider backward compatibility.

Never delete or rewrite an existing migration that has already been applied to shared/production environments unless explicitly instructed.

---

# 10. MODEL RULES

Use Eloquent according to Laravel conventions.

Prefer clear relationships:

- belongsTo
- hasOne
- hasMany
- belongsToMany

Avoid unnecessary repositories.

Do not create repository layers simply because they are considered a generic "enterprise pattern."

Use Actions/Services when they provide meaningful separation of business logic.

Keep models understandable.

---

# 11. BUSINESS LOGIC

Business logic must not be placed primarily in:

- Blade views
- Livewire templates
- JavaScript
- controllers containing large procedural blocks

Keep business logic in appropriate:

- domain services
- actions
- models
- policies
- jobs
- dedicated calculation classes

Use dependency injection.

Keep responsibilities focused.

---

# 12. LIVEWIRE RULES

Use Livewire for interactive application UI.

Livewire components should:

- validate input
- authorize actions
- call appropriate application/domain logic
- expose only necessary state

Do not put large business calculations directly into Livewire components.

Do not trust Livewire state without server-side validation and authorization.

---

# 13. FLUX UI RULES

Use Flux UI components where an equivalent component exists.

Prefer consistent UI patterns for:

- forms
- buttons
- dialogs
- dropdowns
- tables
- notifications
- navigation
- inputs
- validation messages

Do not introduce another UI component library without explicit approval.

UI must remain functional and accessible.

---

# 14. AUTHORIZATION

Authorization is mandatory.

Never assume that hiding a button is sufficient security.

Every sensitive action must be authorized server-side.

Use Laravel:

- Policies
- Gates
- Middleware
- permission checks
- resource ownership checks
- organizational scope checks

Example:

A user seeing:

/employees/{id}

does NOT automatically mean the user is authorized to access that employee.

Prevent IDOR.

---

# 15. SENSITIVE DATA

The system contains:

- personal information
- salary
- bank accounts
- BPJS
- tax information
- private documents
- attendance/location information
- biometric information

Treat these as sensitive.

Never expose sensitive data unnecessarily.

Never include sensitive data in debug output.

Never log:

- passwords
- tokens
- API keys
- OAuth credentials
- face templates
- unnecessary personal data

---

# 16. BIOMETRIC DATA

Face biometric data is highly sensitive.

The Face AI system is separate from Laravel.

Laravel communicates with the Face AI service through a defined service interface/API.

Do NOT place face inference directly into controllers.

Do NOT store face templates in Google Drive.

Do NOT create unrestricted endpoints for:

- face templates
- biometric embeddings
- raw biometric records

Do not expose biometric templates to ordinary HR users.

Biometric operations must be auditable where required.

---

# 17. ATTENDANCE

Attendance may use:

- authenticated identity
- registered device
- GPS
- geofence
- face verification
- liveness
- work schedule

Never trust a client-provided value such as:

verified=true

The backend must independently validate important attendance conditions.

Preserve raw attendance events where required.

Manual attendance changes must be auditable.

Example:

Original:
07:58

Adjusted:
07:30

The system must preserve the original event and record:

- adjustment
- reason
- actor
- timestamp
- approval if required

---

# 18. LEAVE

Leave workflow must respect the approved state machine.

Typical flow:

DRAFT
→ SUBMITTED
→ PENDING_APPROVAL
→ APPROVED / REJECTED

Only approved leave may affect attendance/absence processing.

Do not directly manipulate attendance from a leave controller unless the architecture explicitly requires it.

Use appropriate domain/application logic.

---

# 19. PAYROLL

Payroll is a high-risk domain.

Never hardcode regulatory rates or tax rules inside controllers.

Payroll calculations must be:

- deterministic
- testable
- auditable
- configuration/rule driven where appropriate
- effective-dated where required

Finalized payroll must not be silently modified.

Corrections must use approved adjustment mechanisms.

Never change payroll calculation logic without tests.

---

# 20. BPJS AND TAX

Do not assume regulatory rates are permanent.

BPJS and PPh 21 rules must support effective dates where appropriate.

When implementing a regulatory calculation:

1. Identify the source/rule.
2. Document the effective period.
3. Write tests.
4. Avoid hardcoded unexplained constants.

If the current regulation is unclear, STOP and ask for clarification or request verification.

---

# 21. DOCTOR DOMAIN

Doctors are separate from employees.

A doctor MAY have an Employee relationship.

Possible:

Doctor
+
Employee
+
Payroll
+
Attendance
+
Leave

But:

MEDICAL SERVICE FEES / JASA MEDIS ARE OUT OF SCOPE.

Never introduce jasa medis calculations unless explicitly approved.

---

# 22. GOOGLE DRIVE

Google Drive is used for document/object storage.

Examples:

- KTP
- KK
- Ijazah
- contracts
- STR
- SIP
- certificates
- HR documents
- other approved attachments

Use a storage abstraction.

Application code should not scatter Google Drive API calls across controllers and Livewire components.

Prefer:

DocumentService
→ StorageProvider
→ GoogleDriveStorage

Database stores document metadata and Google Drive identifiers.

Google Drive is NOT the source of truth for:

- employee records
- payroll
- attendance
- leave
- authorization
- system configuration

---

# 23. FILE SECURITY

All sensitive document access must be authorized by Laravel.

Do not expose permanent unrestricted Google Drive URLs for private documents.

Validate uploads for:

- size
- MIME type
- extension
- authorization
- allowed document type

Log sensitive document operations where required.

---

# 24. API AND SERVICE SECURITY

External/internal services must be authenticated.

Do not trust:

- client-provided roles
- client-provided permissions
- client-provided verification flags
- client-provided payroll values
- client-provided attendance approval

The server is authoritative.

---

# 25. TESTING

Every business-critical feature must include automated tests.

At minimum test:

- authorization
- validation
- business rules
- database relationships
- state transitions
- failure cases
- unauthorized cases

High-risk domains require especially strong tests:

- attendance
- leave
- payroll
- BPJS
- tax
- documents
- biometric integration

Do not consider a feature complete merely because the UI works.

---

# 26. TEST EXECUTION

After implementation, run the smallest relevant test suite first.

Then run the broader test suite when appropriate.

If tests fail:

DO NOT hide the failure.

Report:

- failing test
- reason
- affected area
- whether the failure was caused by the current change

---

# 27. CODE STYLE

Follow existing Laravel project conventions.

Use:

- strict typing where appropriate
- meaningful names
- small methods
- clear responsibilities
- dependency injection
- Laravel conventions

Avoid:

- unnecessary abstractions
- duplicated business logic
- magic numbers
- unexplained constants
- giant controllers
- giant Livewire components
- unnecessary repositories
- premature microservices

---

# 28. SECURITY BEFORE CONVENIENCE

When choosing between:

A. easier implementation but weaker security

and

B. slightly more complex implementation with appropriate security

prefer B for sensitive hospital data, unless there is a documented reason otherwise.

Do not weaken:

- authorization
- auditability
- encryption
- validation
- data isolation

merely to make development easier.

---

# 29. DOCUMENTATION

When implementing a significant architectural decision, update the relevant `/docs` file.

Do not let documentation silently become inconsistent with the implementation.

If implementation intentionally differs from the approved architecture:

STOP and report the discrepancy.

---

# 30. GIT SAFETY

Before making significant changes:

Inspect:

- git status
- current branch
- recent relevant commits

Do not:

- force push
- reset shared history
- delete unrelated changes
- overwrite user changes
- modify unrelated files

Never commit secrets.

Never commit:

- `.env`
- OAuth credentials
- API keys
- private certificates
- biometric templates
- production credentials

unless explicitly approved and securely handled.

---

# 31. FILE SCOPE

Modify only files necessary for the current task.

Do not perform unrelated refactoring.

Do not "clean up" unrelated code while implementing a feature.

If unrelated problems are discovered, report them separately.

---

# 32. BEFORE EVERY FEATURE

Before implementation, provide:

## Understanding

What requirement is being implemented?

## Dependencies

What existing modules are required?

## Files

Which files are expected to change?

## Database

Are migrations required?

## Security

What authorization/security considerations exist?

## Tests

What tests will be added or modified?

Then implement.

---

# 33. AFTER EVERY FEATURE

Report:

## Implemented

What was implemented?

## Files Changed

List files.

## Database Changes

List migrations/schema changes.

## Tests

List tests executed and results.

## Security

Explain relevant authorization/security controls.

## Remaining Issues

List unresolved issues.

## Next Step

Suggest the next logical implementation task.

Do not automatically start the next feature.

---

# 34. STOP CONDITIONS

STOP and ask the project owner when:

- requirements conflict
- architecture conflicts with requirements
- database design must materially change
- a security decision is unclear
- regulatory rules are unclear
- sensitive-data handling is unclear
- an irreversible operation is required
- production data may be affected
- an existing user change may be overwritten
- a major dependency must be changed
- a new external service is required
- the requested feature is outside approved scope

Never guess in these situations.

---

# 35. DEFINITION OF DONE

A feature is NOT DONE merely because code exists.

A feature is considered done only when:

- requirements are satisfied
- authorization exists
- validation exists
- database integrity is maintained
- relevant tests exist
- tests pass
- UI states are handled
- error states are handled
- audit requirements are addressed
- documentation is updated where necessary
- no unrelated files were modified
- implementation is reviewed

---

# 36. FINAL RULE

The AI coding agent is an implementation assistant.

The project owner remains the decision maker.

Do not make major architectural, security, regulatory, or business decisions silently.

When uncertain:

STOP
→ EXPLAIN
→ ASK

Do not guess.