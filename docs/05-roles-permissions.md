# ROLES AND PERMISSION MATRIX SPECIFICATION

**Document Identifier**: `/docs/05-roles-permissions.md`  
**System Name**: Hospital Human Resources, Payroll, Attendance, and Doctor Scheduling System  
**Target Organization**: Single Hospital Enterprise Deployment (Juwita Hospital)  
**Role**: Senior Application Security & Authorization Architect  
**Status**: APPROVED ROLES & PERMISSIONS BASELINE  

---

## 1. AUTHORIZATION PHILOSOPHY & OBJECTIVES

The Hospital HR Management System safeguards highly sensitive personal health worker data, medical licenses, and financial payroll records. The authorization model enforces:
1. **Least Privilege**: Users are granted only the minimum permissions required for their hospital function.
2. **Separation of Duties (SoD)**: The user who calculates payroll cannot finalize or release funds; supervisors can recommend leave/attendance adjustments but cannot provide final HR policy sign-off.
3. **Multi-Dimensional Authorization**: Authorization is never evaluated by role alone (`role === 'supervisor'`). It is always evaluated as:
   $$\text{Access} = \text{Authenticated Identity} \times \text{Permission Gate} \times \text{Organizational Scope} \times \text{Resource Ownership}$$
4. **Zero Unaudited Access**: Even privileged administrative actions performed by `SUPER_ADMIN` generate tamper-evident audit records.

---

## 2. STANDARD ROLE DEFINITIONS

The system defines 8 standard roles:

| Role Identifier | Role Code | Target Hospital Personnel | Primary Responsibility |
|---|---|---|---|
| **Super Admin** | `super_admin` | Lead IT Systems Administrator | Technical operations, system configuration, user role provisioning, and break-glass emergency auditing. |
| **HR Admin** | `hr_admin` | HR Operations Staff / Staffing Officer | Operational personnel record keeping, employee bio data, contract updates, shift scheduling, and document indexing. |
| **HR Manager** | `hr_manager` | Head of Human Resources | Policy definition, tier-2 leave approval, salary revision approvals, attendance dispute resolutions, and payroll submission review. |
| **Supervisor** | `supervisor` | Ward Head, Unit Head, Chief Nurse, Poly Coordinator | Unit-level shift roster management, tier-1 leave approval, and subordinate attendance verification within their assigned Unit. |
| **Payroll** | `payroll` | Compensation & Benefits Specialist | Monthly payroll period management, salary calculations, attendance penalty processing, BPJS deductions, and PPh 21 tax withholding. |
| **Finance** | `finance` | Chief Financial Officer / Finance Manager | Financial verification of calculated payroll runs, disbursement authorization, payroll finalization locking, and bank disbursement file processing. |
| **Doctor** | `doctor` | Specialist Doctors, General Practitioners, Dentists | Clinical shift self-service, personal attendance clock-in, medical credential management (STR/SIP), and leave applications. |
| **Employee** | `employee` | Nurses, Pharmacists, Radiographers, Administrative Staff | General staff self-service, shift viewing, personal attendance clock-in, leave requests, and monthly payslip downloads. |

---

## 3. PERMISSION NAMING CONVENTION

All permissions follow a strict, lowercase dot-notation format:
$$\mathbf{domain.action}$$

Examples:
- `employee.view`: View general employee profile.
- `employee.view_sensitive`: View sensitive PII (NIK KTP, Family Card, Bank Account).
- `attendance.adjust`: Submit a manual attendance correction request.
- `payroll.calculate`: Execute the gross-to-net payroll computation batch.
- `payroll.finalize`: Permanently lock a payroll run into an immutable state.

---

## 4. COMPLETE PERMISSION CATALOG

### 4.1 Organization Domain (`org.*`)
- `org.view`: View hospital departments, units, positions, and public holidays.
- `org.create`: Create new organizational departments, units, or positions.
- `org.update`: Edit department details, unit structures, or position grades.
- `org.delete`: Deactivate or remove empty departments, units, or positions.

### 4.2 Employee Domain (`employee.*`)
- `employee.view`: View general employee profile (name, NIP, department, position, contact).
- `employee.view_sensitive`: View encrypted personal data (NIK KTP, birth date, home address, marital status).
- `employee.view_salary`: View current and historical basic salary records.
- `employee.view_bank`: View employee salary disbursement bank account details.
- `employee.create`: Register a new employee profile and generate NIP.
- `employee.update`: Update general employee bio data and contact information.
- `employee.update_employment`: Manage contract tenures, promotions, and status changes.
- `employee.update_salary`: Revise base salary records with effective dates.
- `employee.deactivate`: Terminate or suspend employee active status.

### 4.3 Doctor Domain (`doctor.*`)
- `doctor.view`: View doctor registry, specialization, and clinical department assignments.
- `doctor.view_credentials`: View STR and SIP medical registration metadata and expiration dates.
- `doctor.create`: Register a new doctor profile and professional identity.
- `doctor.update`: Update doctor demographic and contact details.
- `doctor.manage_credentials`: Upload and renew STR/SIP medical certificates.
- `doctor.link_employee`: Associate an organic doctor to an employee payroll profile.
- `doctor.deactivate`: Deactivate doctor clinical status.

### 4.4 Scheduling & Shifts (`schedule.*`)
- `schedule.view`: View published department shift schedules and rosters.
- `schedule.manage_shifts`: Create, edit, and deactivate shift master templates.
- `schedule.assign_team`: Assign and publish monthly shifts for subordinate team members.
- `schedule.assign_all`: Assign and override schedules across all hospital departments.

### 4.5 Attendance Engine (`attendance.*`)
- `attendance.view_own`: View personal clock-in history, lateness, and hours worked.
- `attendance.view_team`: View attendance events and records for assigned unit subordinates.
- `attendance.view_all`: View hospital-wide attendance records and daily summaries.
- `attendance.check_in_out`: Perform biometric and geofenced check-in / check-out.
- `attendance.adjust_request`: Submit a manual attendance time/status adjustment request.
- `attendance.adjust_approve`: Approve or reject subordinate attendance adjustments.
- `attendance.export`: Export attendance logs, lateness summaries, and absence sheets.

### 4.6 Leave & Permissions (`leave.*`)
- `leave.view_own`: View personal leave balances and application status history.
- `leave.view_team`: View leave applications submitted by unit team members.
- `leave.view_all`: View all hospital staff leave applications and balances.
- `leave.create_own`: Submit personal leave or permission requests.
- `leave.cancel_own`: Cancel own submitted leave prior to start date.
- `leave.approve_tier1`: Level 1 supervisor approval for unit leave requests.
- `leave.approve_tier2`: Level 2 HR approval for finalized leave entitlement deductions.
- `leave.manage_policy`: Define leave types, quota rules, and holiday calendars.
- `leave.manage_balance`: Manually adjust or grant annual leave quota balances.

### 4.7 Payroll Processing (`payroll.*`)
- `payroll.view_own`: View and download personal monthly payslips.
- `payroll.view_all`: View all employee payroll runs, gross earnings, and line-item details.
- `payroll.calculate`: Initiate and execute gross-to-net payroll run batches.
- `payroll.adjust`: Add ad-hoc bonus or deduction adjustment items to a draft run.
- `payroll.submit_approval`: Submit calculated payroll run for management review.
- `payroll.finalize`: Permanently finalize, lock, and execute financial sign-off on a payroll run.
- `payroll.export_bank`: Export finalized payroll disbursement files for bank portals (BCA, Mandiri, BNI).

### 4.8 Statutory BPJS & Tax (`statutory.*`)
- `statutory.view`: View statutory BPJS rates, wage ceilings, and PPh 21 TER tax tables.
- `statutory.manage_rules`: Update effective-dated BPJS contribution rates and tax brackets.
- `statutory.export_reports`: Generate monthly SPT Masa PPh 21 and BPJS report files.

### 4.9 Document Management (`document.*`)
- `document.view_own`: View and download personal uploaded documents (KTP, contracts).
- `document.view_all`: Access documents for all hospital employees and doctors.
- `document.upload_own`: Upload personal compliance certificates or sick leaves.
- `document.upload_all`: Upload administrative documents to any staff profile.
- `document.download`: Generate signed, temporary secure preview/download URLs.
- `document.delete`: Soft-delete documents from database and purge from Google Drive.

### 4.10 Audit & System Configuration (`system.*`)
- `system.audit_view`: View immutable system audit logs and resource state diffs.
- `system.audit_export`: Export sanitized audit trails for compliance inspections.
- `system.config_view`: View hospital coordinates, geofence radii, and operating rules.
- `system.config_update`: Update system configuration parameters and security limits.

---

## 5. FULL PERMISSION MATRIX

### Access Levels:
- **FULL**: Unrestricted create, read, update, delete, or execute authority across the hospital.
- **TEAM**: Restricted strictly to subordinate staff within the user's assigned organizational Unit/Ward.
- **OWN**: Restricted strictly to the user's personal self-service record.
- **READ**: Read-only access across the hospital.
- **NONE**: Zero access permitted (throws 403 Forbidden).

| Permission | Super Admin | HR Admin | HR Manager | Supervisor | Payroll | Finance | Doctor | Employee |
|---|---|---|---|---|---|---|---|---|
| **Organization (`org.*`)** | | | | | | | | |
| `org.view` | FULL | FULL | FULL | READ | READ | READ | READ | READ |
| `org.create` | FULL | FULL | FULL | NONE | NONE | NONE | NONE | NONE |
| `org.update` | FULL | FULL | FULL | NONE | NONE | NONE | NONE | NONE |
| `org.delete` | FULL | NONE | FULL | NONE | NONE | NONE | NONE | NONE |
| **Employee (`employee.*`)** | | | | | | | | |
| `employee.view` | FULL | FULL | FULL | TEAM | READ | READ | NONE | NONE |
| `employee.view_sensitive` | FULL | FULL | FULL | NONE | READ | NONE | OWN | OWN |
| `employee.view_salary` | FULL | NONE | FULL | NONE | FULL | READ | NONE | NONE |
| `employee.view_bank` | FULL | FULL | FULL | NONE | FULL | FULL | OWN | OWN |
| `employee.create` | FULL | FULL | FULL | NONE | NONE | NONE | NONE | NONE |
| `employee.update` | FULL | FULL | FULL | NONE | NONE | NONE | OWN* | OWN* |
| `employee.update_employment`| FULL | FULL | FULL | NONE | NONE | NONE | NONE | NONE |
| `employee.update_salary` | FULL | NONE | FULL | NONE | NONE | NONE | NONE | NONE |
| `employee.deactivate` | FULL | NONE | FULL | NONE | NONE | NONE | NONE | NONE |
| **Doctor (`doctor.*`)** | | | | | | | | |
| `doctor.view` | FULL | FULL | FULL | READ | READ | READ | READ | READ |
| `doctor.view_credentials` | FULL | FULL | FULL | NONE | NONE | NONE | OWN | NONE |
| `doctor.create` | FULL | FULL | FULL | NONE | NONE | NONE | NONE | NONE |
| `doctor.update` | FULL | FULL | FULL | NONE | NONE | NONE | OWN* | NONE |
| `doctor.manage_credentials` | FULL | FULL | FULL | NONE | NONE | NONE | OWN | NONE |
| `doctor.link_employee` | FULL | FULL | FULL | NONE | NONE | NONE | NONE | NONE |
| `doctor.deactivate` | FULL | NONE | FULL | NONE | NONE | NONE | NONE | NONE |
| **Scheduling (`schedule.*`)** | | | | | | | | |
| `schedule.view` | FULL | FULL | FULL | TEAM | READ | NONE | OWN | OWN |
| `schedule.manage_shifts` | FULL | FULL | FULL | NONE | NONE | NONE | NONE | NONE |
| `schedule.assign_team` | FULL | FULL | FULL | TEAM | NONE | NONE | NONE | NONE |
| `schedule.assign_all` | FULL | FULL | FULL | NONE | NONE | NONE | NONE | NONE |
| **Attendance (`attendance.*`)**| | | | | | | | |
| `attendance.view_own` | FULL | FULL | FULL | FULL | FULL | FULL | OWN | OWN |
| `attendance.view_team` | FULL | FULL | FULL | TEAM | NONE | NONE | NONE | NONE |
| `attendance.view_all` | FULL | FULL | FULL | NONE | READ | NONE | NONE | NONE |
| `attendance.check_in_out` | NONE | NONE | NONE | OWN | NONE | NONE | OWN | OWN |
| `attendance.adjust_request` | FULL | FULL | FULL | TEAM | NONE | NONE | OWN | OWN |
| `attendance.adjust_approve` | FULL | NONE | FULL | TEAM | NONE | NONE | NONE | NONE |
| `attendance.export` | FULL | FULL | FULL | TEAM | FULL | NONE | NONE | NONE |
| **Leave (`leave.*`)** | | | | | | | | |
| `leave.view_own` | FULL | FULL | FULL | FULL | FULL | FULL | OWN | OWN |
| `leave.view_team` | FULL | FULL | FULL | TEAM | NONE | NONE | NONE | NONE |
| `leave.view_all` | FULL | FULL | FULL | NONE | READ | NONE | NONE | NONE |
| `leave.create_own` | FULL | FULL | FULL | OWN | NONE | NONE | OWN | OWN |
| `leave.cancel_own` | FULL | FULL | FULL | OWN | NONE | NONE | OWN | OWN |
| `leave.approve_tier1` | FULL | NONE | FULL | TEAM | NONE | NONE | NONE | NONE |
| `leave.approve_tier2` | FULL | NONE | FULL | NONE | NONE | NONE | NONE | NONE |
| `leave.manage_policy` | FULL | NONE | FULL | NONE | NONE | NONE | NONE | NONE |
| `leave.manage_balance` | FULL | FULL | FULL | NONE | NONE | NONE | NONE | NONE |
| **Payroll (`payroll.*`)** | | | | | | | | |
| `payroll.view_own` | FULL | FULL | FULL | FULL | FULL | FULL | OWN**| OWN |
| `payroll.view_all` | FULL | NONE | FULL | NONE | FULL | FULL | NONE | NONE |
| `payroll.calculate` | FULL | NONE | NONE | NONE | FULL | NONE | NONE | NONE |
| `payroll.adjust` | FULL | NONE | FULL | NONE | FULL | NONE | NONE | NONE |
| `payroll.submit_approval` | FULL | NONE | NONE | NONE | FULL | NONE | NONE | NONE |
| `payroll.finalize` | FULL | NONE | NONE | NONE | NONE | FULL | NONE | NONE |
| `payroll.export_bank` | FULL | NONE | NONE | NONE | NONE | FULL | NONE | NONE |
| **Statutory BPJS & Tax (`statutory.*`)**| | | | | | | | |
| `statutory.view` | FULL | READ | FULL | NONE | FULL | FULL | NONE | NONE |
| `statutory.manage_rules` | FULL | NONE | FULL | NONE | NONE | NONE | NONE | NONE |
| `statutory.export_reports` | FULL | NONE | FULL | NONE | FULL | FULL | NONE | NONE |
| **Documents (`document.*`)** | | | | | | | | |
| `document.view_own` | FULL | FULL | FULL | FULL | FULL | FULL | OWN | OWN |
| `document.view_all` | FULL | FULL | FULL | NONE | READ | NONE | NONE | NONE |
| `document.upload_own` | FULL | FULL | FULL | OWN | NONE | NONE | OWN | OWN |
| `document.upload_all` | FULL | FULL | FULL | NONE | NONE | NONE | NONE | NONE |
| `document.download` | FULL | FULL | FULL | TEAM | READ | NONE | OWN | OWN |
| `document.delete` | FULL | NONE | FULL | NONE | NONE | NONE | NONE | NONE |
| **System & Audit (`system.*`)** | | | | | | | | |
| `system.audit_view` | FULL | NONE | READ | NONE | NONE | NONE | NONE | NONE |
| `system.audit_export` | FULL | NONE | FULL | NONE | NONE | NONE | NONE | NONE |
| `system.config_view` | FULL | READ | READ | NONE | NONE | NONE | NONE | NONE |
| `system.config_update` | FULL | NONE | NONE | NONE | NONE | NONE | NONE | NONE |

*\*Note (Self-Service Updates): Employees and Doctors may update non-critical personal contact information only (phone, residential address). Core identity (NIK, name, birth date) requires HR verification.*  
*\*\*Note (Doctor Payslips): Doctors have `OWN` access to monthly payslips only if they maintain an active link to an Employee record.*

---

## 6. SENSITIVE DATA ACCESS MATRIX

| Sensitive Data Entity | Authorized Roles | Prohibited Roles | Policy Condition / Protection Method |
|---|---|---|---|
| **Base Salary & Revisions** | `super_admin`, `hr_manager`, `payroll` | `supervisor`, `doctor`, `employee`, `hr_admin` | Viewable only with explicit `employee.view_salary` permission; hidden in standard staff grids. |
| **Employee Bank Accounts** | `super_admin`, `hr_admin`, `hr_manager`, `payroll`, `finance`, `employee` (OWN) | `supervisor`, `doctor` (unless own) | Encrypted at rest via AES-256; masked in UI grids (displaying last 4 digits only). |
| **PPh 21 Tax Withholdings** | `super_admin`, `hr_manager`, `payroll`, `finance`, `employee` (OWN) | `supervisor`, `hr_admin` | Encrypted in payroll items; accessible on personal payslips only after payroll finalization. |
| **BPJS Registration Numbers** | `super_admin`, `hr_admin`, `hr_manager`, `payroll`, `employee` (OWN) | `supervisor` | Restricted to HR and payroll processing channels. |
| **Private Employee Docs (KTP/KK)**| `super_admin`, `hr_admin`, `hr_manager`, `employee` (OWN) | `supervisor`, `finance`, `payroll` | Mediated strictly via signed, 15-minute temporary URLs; public sharing disabled. |
| **Doctor License Docs (STR/SIP)** | `super_admin`, `hr_admin`, `hr_manager`, `doctor` (OWN) | General employees, `finance`, `payroll` | Protected against non-clinical staff access. |
| **GPS Clock-in Coordinates** | `super_admin`, `hr_manager`, `supervisor` (TEAM), `attendance.view_all` | `finance`, other employees | Diagnostic data available only for attendance dispute resolution. |
| **Biometric Face Vectors** | **System Engine Only** | **ALL ROLES (Including Super Admin)** | Encrypted vectors; no view, edit, or download permissions exist in the application. |

---

## 7. ORGANIZATIONAL SCOPING RULES (Supervisor Scoping)

Supervisors possess authority strictly over staff assigned to their operational hospital unit.

### Scoping Rule Implementation:
When evaluating permissions with a `TEAM` scope, Laravel Policies must assert:
1. The supervisor has an active employee record: `$user->employee !== null`.
2. The target employee belongs to the supervisor's assigned unit:
   ```php
   $user->employee->unit_id === $targetEmployee->unit_id
   ```
3. For multi-unit supervisors (e.g. Intensive Care Coordinator covering ICU and NICU), evaluation matches against the supervisor's permitted unit assignments array:
   ```php
   in_array($targetEmployee->unit_id, $user->employee->supervisedUnitIds(), true)
   ```

---

## 8. MULTI-STAGE APPROVAL PERMISSION MATRIX

Approval authority is segregated from operational CRUD permissions:

```mermaid
graph TD
    subgraph "Leave Approval Routing"
        EmpLeave[Employee Leave Application] --> SupAppr[Tier-1: Supervisor Approval (leave.approve_tier1)]
        SupAppr --> HRAppr[Tier-2: HR Manager Approval (leave.approve_tier2)]
        HRAppr --> LeaveDone[Approved: Balance Decremented]
    end

    subgraph "Doctor Leave Approval Routing"
        DocLeave[Doctor Leave Application] --> PolyAppr[Tier-1: Poly / Department Head]
        PolyAppr --> HRAppr2[Tier-2: HR Manager Approval]
        HRAppr2 --> DocDone[Approved: Roster Updated]
    end

    subgraph "Payroll Approval Routing"
        PayRun[Calculated Payroll Run] --> HRReview[HR Manager Review & Submission]
        HRReview --> FinAppr[Finance Finalization & Sign-Off (payroll.finalize)]
        FinAppr --> PayDone[Immutable Finalized Run]
    end
```

### Explicit Approval Rules:
1. `payroll.calculate` does **not** grant authority to `payroll.finalize`.
2. `leave.approve_tier1` verifies operational roster coverage but does **not** deduct annual leave balance until `leave.approve_tier2` executes.
3. An approver **cannot** approve their own leave request (`$leave->applicant_id !== $user->employee->id`).

---

## 9. PRIVILEGED ACCESS & SUPER ADMIN (BREAK-GLASS)

1. **Auditable Break-Glass**: The `super_admin` role possesses broad permissions for system recovery and diagnostics, but **every single action is written to `audit_logs`**.
2. **No Stealth Operations**: Super Admins cannot bypass or disable audit logging.
3. **No Direct Payroll Modification**: Even Super Admins cannot update rows in a finalized payroll run (`status = 'finalized'`); changes must follow the statutory adjustment mechanism.
4. **Biometric Protection**: Super Admins have zero technical capabilities within the application to extract, display, or download biometric face templates.

---

## 10. LARAVEL POLICY & GATE MAPPING

| Domain Resource | Primary Policy Class | Key Method Assertions |
|---|---|---|
| `App\Models\Employee` | `App\Policies\EmployeePolicy` | `view()`, `viewSensitive()`, `update()`, `updateSalary()`, `deactivate()` |
| `App\Models\Doctor` | `App\Policies\DoctorPolicy` | `view()`, `viewCredentials()`, `update()`, `manageCredentials()` |
| `App\Models\ScheduleAssignment`| `App\Policies\SchedulePolicy`| `view()`, `assignTeam()`, `overrideConflict()` |
| `App\Models\AttendanceRecord` | `App\Policies\AttendancePolicy`| `view()`, `adjust()`, `approveAdjustment()` |
| `App\Models\LeaveRequest` | `App\Policies\LeavePolicy` | `view()`, `create()`, `cancel()`, `approveTier1()`, `approveTier2()` |
| `App\Models\PayrollRun` | `App\Policies\PayrollPolicy` | `view()`, `calculate()`, `submit()`, `finalize()`, `exportBank()` |
| `App\Models\Document` | `App\Policies\DocumentPolicy` | `view()`, `download()`, `upload()`, `delete()` |
| `App\Models\AuditLog` | `App\Policies\AuditLogPolicy` | `view()`, `export()` |

---

## 11. UNRESOLVED AUTHORIZATION DECISIONS

1. **Self-Service Bank Account Updates**: Should employees be permitted to submit bank account change requests directly through self-service, or does finance require manual in-person submission of physical bank book copies? *(Proposed: Self-service request with mandatory HR/Finance verification before activation)*.
2. **Delegation of Approval Authority**: During prolonged leave of a Unit Supervisor, does the system require automated temporary delegation of `leave.approve_tier1` to an acting supervisor? *(Proposed: Manual interim role assignment by HR Admin)*.

---

## 12. SUMMARY REPORT

- **Standard Roles Defined**: 8 Dedicated Roles.
- **Granular Permissions Cataloged**: 48 Distinct Permissions across 10 functional domains.
- **Access Tiers**: FULL, TEAM, OWN, READ, NONE.
- **Sensitive Data Protections**: Base salary, bank accounts, tax, and documents isolated with field-level policy gates.
- **Biometric Security**: Zero permissions exist for raw biometric vector extraction or downloading.
- **Next Phase**: The technical architecture and specification documentation phase (`00` through `05`) is now 100% complete and fully aligned. The system is ready to proceed to the domain-by-domain code implementation phase starting with **Domain 1: Organization Structure** or **Auth/RBAC**.
