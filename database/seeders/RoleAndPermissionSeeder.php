<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Roles definitions
        $rolesData = [
            'super_admin' => ['display_name' => 'Super Admin', 'description' => 'Lead IT Systems Administrator with full emergency break-glass authority'],
            'hr_admin' => ['display_name' => 'HR Admin', 'description' => 'HR Operations Staff managing personnel records, shifts, and credentials'],
            'hr_manager' => ['display_name' => 'HR Manager', 'description' => 'Head of Human Resources with tier-2 approval and compensation management'],
            'supervisor' => ['display_name' => 'Supervisor', 'description' => 'Ward/Unit Head managing rosters and tier-1 approvals for assigned team'],
            'payroll' => ['display_name' => 'Payroll', 'description' => 'Compensation specialist executing monthly payroll runs, BPJS, and taxes'],
            'finance' => ['display_name' => 'Finance', 'description' => 'Finance manager with payroll finalization and disbursement authority'],
            'doctor' => ['display_name' => 'Doctor', 'description' => 'Medical practitioner clinical self-service'],
            'employee' => ['display_name' => 'Employee', 'description' => 'General hospital staff self-service'],
        ];

        $roles = [];
        foreach ($rolesData as $roleName => $meta) {
            $roles[$roleName] = Role::firstOrCreate(
                ['name' => $roleName],
                ['display_name' => $meta['display_name'], 'description' => $meta['description']]
            );
        }

        // 2. Permissions catalog (48 permissions)
        $permissions = [
            // Organization
            'org.view' => 'org',
            'org.create' => 'org',
            'org.update' => 'org',
            'org.delete' => 'org',

            // Employee
            'employee.view' => 'employee',
            'employee.view_sensitive' => 'employee',
            'employee.view_salary' => 'employee',
            'employee.view_bank' => 'employee',
            'employee.create' => 'employee',
            'employee.update' => 'employee',
            'employee.update_employment' => 'employee',
            'employee.update_salary' => 'employee',
            'employee.deactivate' => 'employee',

            // Doctor
            'doctor.view' => 'doctor',
            'doctor.view_credentials' => 'doctor',
            'doctor.create' => 'doctor',
            'doctor.update' => 'doctor',
            'doctor.manage_credentials' => 'doctor',
            'doctor.link_employee' => 'doctor',
            'doctor.deactivate' => 'doctor',

            // Scheduling
            'schedule.view' => 'schedule',
            'schedule.manage_shifts' => 'schedule',
            'schedule.assign_team' => 'schedule',
            'schedule.assign_all' => 'schedule',

            // Attendance
            'attendance.view_own' => 'attendance',
            'attendance.view_team' => 'attendance',
            'attendance.view_all' => 'attendance',
            'attendance.check_in_out' => 'attendance',
            'attendance.adjust_request' => 'attendance',
            'attendance.adjust_approve' => 'attendance',
            'attendance.export' => 'attendance',

            // Leave
            'leave.view_own' => 'leave',
            'leave.view_team' => 'leave',
            'leave.view_all' => 'leave',
            'leave.create_own' => 'leave',
            'leave.cancel_own' => 'leave',
            'leave.approve_tier1' => 'leave',
            'leave.approve_tier2' => 'leave',
            'leave.manage_policy' => 'leave',
            'leave.manage_balance' => 'leave',

            // Payroll
            'payroll.view_own' => 'payroll',
            'payroll.view_all' => 'payroll',
            'payroll.calculate' => 'payroll',
            'payroll.adjust' => 'payroll',
            'payroll.submit_approval' => 'payroll',
            'payroll.finalize' => 'payroll',
            'payroll.export_bank' => 'payroll',

            // Statutory BPJS & Tax
            'statutory.view' => 'statutory',
            'statutory.manage_rules' => 'statutory',
            'statutory.export_reports' => 'statutory',

            // Documents
            'document.view_own' => 'document',
            'document.view_all' => 'document',
            'document.upload_own' => 'document',
            'document.upload_all' => 'document',
            'document.download' => 'document',
            'document.delete' => 'document',

            // System & Audit
            'system.audit_view' => 'system',
            'system.audit_export' => 'system',
            'system.config_view' => 'system',
            'system.config_update' => 'system',
        ];

        $permissionModels = [];
        foreach ($permissions as $name => $module) {
            $permissionModels[$name] = Permission::firstOrCreate(
                ['name' => $name],
                ['module' => $module, 'description' => "Permission for {$name}"]
            );
        }

        // 3. Map Permissions to Roles per Matrix
        // Super Admin gets all permissions
        $roles['super_admin']->permissions()->sync(array_values(array_map(fn ($p) => $p->id, $permissionModels)));

        // HR Admin
        $hrAdminPerms = [
            'org.view', 'org.create', 'org.update',
            'employee.view', 'employee.view_sensitive', 'employee.view_bank', 'employee.create', 'employee.update', 'employee.update_employment',
            'doctor.view', 'doctor.view_credentials', 'doctor.create', 'doctor.update', 'doctor.manage_credentials', 'doctor.link_employee',
            'schedule.view', 'schedule.manage_shifts', 'schedule.assign_team', 'schedule.assign_all',
            'attendance.view_own', 'attendance.view_team', 'attendance.view_all', 'attendance.adjust_request', 'attendance.export',
            'leave.view_own', 'leave.view_team', 'leave.view_all', 'leave.create_own', 'leave.cancel_own', 'leave.manage_balance',
            'payroll.view_own',
            'statutory.view',
            'document.view_own', 'document.view_all', 'document.upload_own', 'document.upload_all', 'document.download',
            'system.config_view',
        ];
        $this->syncRolePermissions($roles['hr_admin'], $hrAdminPerms, $permissionModels);

        // HR Manager
        $hrManagerPerms = [
            'org.view', 'org.create', 'org.update', 'org.delete',
            'employee.view', 'employee.view_sensitive', 'employee.view_salary', 'employee.view_bank', 'employee.create', 'employee.update', 'employee.update_employment', 'employee.update_salary', 'employee.deactivate',
            'doctor.view', 'doctor.view_credentials', 'doctor.create', 'doctor.update', 'doctor.manage_credentials', 'doctor.link_employee', 'doctor.deactivate',
            'schedule.view', 'schedule.manage_shifts', 'schedule.assign_team', 'schedule.assign_all',
            'attendance.view_own', 'attendance.view_team', 'attendance.view_all', 'attendance.adjust_request', 'attendance.adjust_approve', 'attendance.export',
            'leave.view_own', 'leave.view_team', 'leave.view_all', 'leave.create_own', 'leave.cancel_own', 'leave.approve_tier1', 'leave.approve_tier2', 'leave.manage_policy', 'leave.manage_balance',
            'payroll.view_own', 'payroll.view_all', 'payroll.adjust',
            'statutory.view', 'statutory.manage_rules', 'statutory.export_reports',
            'document.view_own', 'document.view_all', 'document.upload_own', 'document.upload_all', 'document.download', 'document.delete',
            'system.audit_view', 'system.audit_export', 'system.config_view',
        ];
        $this->syncRolePermissions($roles['hr_manager'], $hrManagerPerms, $permissionModels);

        // Supervisor
        $supervisorPerms = [
            'org.view',
            'employee.view',
            'doctor.view',
            'schedule.view', 'schedule.assign_team',
            'attendance.view_own', 'attendance.view_team', 'attendance.check_in_out', 'attendance.adjust_request', 'attendance.adjust_approve', 'attendance.export',
            'leave.view_own', 'leave.view_team', 'leave.create_own', 'leave.cancel_own', 'leave.approve_tier1',
            'payroll.view_own',
            'document.view_own', 'document.upload_own', 'document.download',
        ];
        $this->syncRolePermissions($roles['supervisor'], $supervisorPerms, $permissionModels);

        // Payroll
        $payrollPerms = [
            'org.view',
            'employee.view', 'employee.view_sensitive', 'employee.view_salary', 'employee.view_bank',
            'doctor.view',
            'schedule.view',
            'attendance.view_own', 'attendance.view_all', 'attendance.export',
            'leave.view_own', 'leave.view_all',
            'payroll.view_own', 'payroll.view_all', 'payroll.calculate', 'payroll.adjust', 'payroll.submit_approval',
            'statutory.view', 'statutory.manage_rules', 'statutory.export_reports',
            'document.view_own', 'document.view_all', 'document.download',
        ];
        $this->syncRolePermissions($roles['payroll'], $payrollPerms, $permissionModels);

        // Finance
        $financePerms = [
            'org.view',
            'employee.view', 'employee.view_salary', 'employee.view_bank',
            'doctor.view',
            'payroll.view_own', 'payroll.view_all', 'payroll.finalize', 'payroll.export_bank',
            'statutory.view', 'statutory.export_reports',
            'document.view_own',
        ];
        $this->syncRolePermissions($roles['finance'], $financePerms, $permissionModels);

        // Doctor
        $doctorPerms = [
            'org.view',
            'doctor.view',
            'schedule.view',
            'attendance.view_own', 'attendance.check_in_out', 'attendance.adjust_request',
            'leave.view_own', 'leave.create_own', 'leave.cancel_own',
            'payroll.view_own',
            'document.view_own', 'document.upload_own', 'document.download',
        ];
        $this->syncRolePermissions($roles['doctor'], $doctorPerms, $permissionModels);

        // Employee
        $employeePerms = [
            'org.view',
            'doctor.view',
            'schedule.view',
            'attendance.view_own', 'attendance.check_in_out', 'attendance.adjust_request',
            'leave.view_own', 'leave.create_own', 'leave.cancel_own',
            'payroll.view_own',
            'document.view_own', 'document.upload_own', 'document.download',
        ];
        $this->syncRolePermissions($roles['employee'], $employeePerms, $permissionModels);

        // 4. Default Super Admin User for local development
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@juwitahospital.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole('super_admin');
    }

    /**
     * @param  list<string>  $permissionNames
     * @param  array<string, Permission>  $allPermissions
     */
    protected function syncRolePermissions(Role $role, array $permissionNames, array $allPermissions): void
    {
        $ids = [];
        foreach ($permissionNames as $name) {
            if (isset($allPermissions[$name])) {
                $ids[] = $allPermissions[$name]->id;
            }
        }
        $role->permissions()->sync($ids);
    }
}
