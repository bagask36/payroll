# PROMPT 01 — REQUIREMENTS ENGINEERING

You are acting as a Senior Business Analyst and Software Requirements Engineer.

Read:

- `/docs/00-project-constitution.md`

Do NOT write application code.

Do NOT create migrations.

Do NOT create models.

Do NOT modify the application implementation.

Your task is to transform the approved project scope into a formal Software Requirements Specification for the Hospital HR Management System.

---

# 1. SYSTEM CONTEXT

The system is designed for ONE hospital.

The system manages:

- employees
- doctors
- organization
- employment
- salary
- BPJS
- insurance
- attendance
- schedules
- shifts
- leave
- approvals
- payroll
- PPh 21
- documents
- audit logs

The system does NOT calculate medical service fees / jasa medis.

---

# 2. DEFINE SYSTEM ACTORS

Identify and describe the responsibilities and access needs of:

- Super Admin
- HR Admin
- HR Manager
- Supervisor
- Payroll
- Finance
- Doctor
- Employee

Do not assume every role has unrestricted access.

Define what each actor can:

- view
- create
- update
- approve
- reject
- finalize
- export
- download

---

# 3. DEFINE BUSINESS DOMAINS

Create a domain map covering at least:

1. Authentication
2. Authorization
3. Organization
4. Employee
5. Doctor
6. Employment
7. Compensation
8. BPJS
9. Insurance
10. Scheduling
11. Attendance
12. Leave
13. Approval
14. Payroll
15. Tax
16. Documents
17. Audit
18. Reporting
19. System Configuration

For each domain define:

- purpose
- primary users
- major entities
- major workflows
- dependencies
- major business rules

---

# 4. EMPLOYEE REQUIREMENTS

Define requirements for:

- employee identity
- personal data
- contact
- address
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

Support employment history.

Support salary history.

Do not overwrite historical salary information without preserving history.

---

# 5. DOCTOR REQUIREMENTS

Define Doctor as a separate domain.

A doctor may optionally have an Employee relationship.

Define:

- doctor profile
- professional identity
- STR/SIP metadata
- department/unit/poli
- schedule
- attendance
- leave
- documents

Explicitly document:

MEDICAL SERVICE FEES / JASA MEDIS ARE OUT OF SCOPE.

---

# 6. ATTENDANCE REQUIREMENTS

Define attendance requirements for:

- employee
- doctor

Attendance should support:

- shift
- schedule
- check-in
- check-out
- lateness
- early leave
- absence
- overtime
- holiday
- weekend
- manual adjustment
- audit trail

Attendance verification may involve:

- authenticated user
- device
- face verification
- liveness
- GPS
- geofence
- schedule

Define the difference between:

- raw attendance event
- processed attendance
- attendance adjustment
- attendance evidence

---

# 7. LEAVE REQUIREMENTS

Define:

- leave types
- leave policy
- leave balance
- leave request
- leave approval
- leave cancellation
- leave history

Workflow:

DRAFT
→ SUBMITTED
→ PENDING APPROVAL
→ APPROVED / REJECTED

Only approved leave affects attendance/absence processing.

Support both employee and doctor leave where enabled.

---

# 8. SCHEDULING REQUIREMENTS

Define:

- shifts
- schedules
- schedule assignment
- doctor schedules
- employee schedules
- holidays
- weekends
- overnight shifts
- schedule conflicts

Define interaction between:

Schedule
+
Leave
+
Attendance

---

# 9. PAYROLL REQUIREMENTS

Define payroll components without hardcoding specific values.

Possible components:

- basic salary
- allowance
- overtime
- bonus
- BPJS
- insurance
- tax
- deductions

Define:

- payroll period
- payroll run
- payroll item
- adjustment
- approval
- finalization

FINALIZED payroll must be immutable.

---

# 10. BPJS REQUIREMENTS

Define support for:

- BPJS Kesehatan
- BPJS Ketenagakerjaan

BPJS calculation rules must be configurable and effective-dated.

Do not assume current rates are permanent.

The final implementation must use verified current regulations before production.

---

# 11. TAX REQUIREMENTS

Define PPh 21 as a configurable payroll component.

Support:

- tax profile
- tax rules
- effective dates
- payroll integration
- reporting

Do not hardcode tax rules without effective dates.

---

# 12. DOCUMENT REQUIREMENTS

Documents may include:

- KTP
- KK
- ijazah
- contract
- STR
- SIP
- certificates
- other HR documents

Document storage:

Application
→ Document Service
→ Google Drive Shared Drive

Database stores metadata and Google Drive file ID.

Face templates are NOT documents.

Face templates must NOT be stored in Google Drive.

---

# 13. APPROVAL REQUIREMENTS

Define configurable approval workflows.

Examples:

Employee:
Employee
→ Supervisor
→ HR

Doctor:
Doctor
→ Head Unit / Department / Poli
→ HR

Payroll:
Payroll
→ Manager/authorized approver
→ Finalized

Do not hardcode one universal approval chain.

---

# 14. REPORTING REQUIREMENTS

Define reports for:

- employees
- doctors
- attendance
- lateness
- absence
- leave
- payroll
- BPJS
- tax
- schedules
- audit

Define export requirements where appropriate.

---

# 15. AUDIT REQUIREMENTS

Identify all actions that require audit logging.

At minimum consider:

- authentication
- employee changes
- salary changes
- leave approval
- attendance adjustment
- payroll processing
- payroll finalization
- document access
- document deletion
- role/permission changes
- configuration changes
- biometric enrollment/administrative actions

---

# 16. NON-FUNCTIONAL REQUIREMENTS

Define requirements for:

- security
- privacy
- availability
- performance
- scalability within a single hospital
- auditability
- maintainability
- backup
- disaster recovery
- observability
- logging
- accessibility
- usability

Do not invent numerical SLAs unless clearly marked as proposed.

---

# 17. SECURITY REQUIREMENTS

Identify sensitive information including:

- personal data
- payroll
- bank account
- BPJS
- tax information
- private documents
- biometric information

Define appropriate security controls.

---

# 18. EXPLICIT OUT-OF-SCOPE

Create an explicit out-of-scope section.

At minimum include:

- multi-tenancy
- multiple hospital management
- medical service fee calculation
- clinical EMR
- patient medical records
- hospital billing
- pharmacy
- laboratory system
- claims processing
- doctor jasa medis calculation

Do not expand the scope without approval.

---

# 19. REQUIREMENT ID FORMAT

Assign stable requirement IDs.

Examples:

HR-EMP-001
HR-DOC-001
HR-ATT-001
HR-LEV-001
HR-PAY-001
HR-DRV-001
HR-FACE-001
HR-AUD-001

Each requirement must have:

- ID
- title
- description
- priority
- actor
- acceptance criteria
- dependencies where applicable

Use priorities:

- MUST
- SHOULD
- COULD
- OUT OF SCOPE

Do not use subjective labels such as "best" or "nice".

---

# 20. ACCEPTANCE CRITERIA

Every MUST requirement must have clear acceptance criteria.

Prefer testable statements.

Example:

HR-ATT-001:
"System shall allow an authorized employee to submit a check-in event only when an active work schedule exists."

Acceptance criteria must define:

- valid case
- invalid case
- expected result

---

# 21. UNRESOLVED QUESTIONS

Create a final section:

## Open Questions

List every business decision that still requires confirmation from the hospital.

Do NOT guess.

Examples:

- maximum geofence radius
- attendance tolerance
- overtime rules
- leave entitlement
- approval hierarchy
- payroll cutoff
- document retention
- attendance evidence retention

---

# 22. OUTPUT

Create:

`/docs/01-requirements.md`

Do not modify application code.

At the end of your response provide:

1. Number of functional requirements
2. Number of non-functional requirements
3. Number of open questions
4. Major assumptions
5. Dependencies
6. Recommended next document

Do not continue to architecture implementation automatically.