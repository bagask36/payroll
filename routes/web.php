<?php

use App\Http\Controllers\PayrollPrintController;
use App\Livewire\Attendance\AttendanceIndex;
use App\Livewire\Attendance\ClockInOut;
use App\Livewire\Audit\AuditLogIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Doctor\DoctorIndex;
use App\Livewire\Document\DocumentIndex;
use App\Livewire\Employee\EmployeeIndex;
use App\Livewire\Employee\EmployeeShow;
use App\Livewire\Leave\LeaveApply;
use App\Livewire\Leave\LeaveIndex;
use App\Livewire\Master\MasterIndex;
use App\Livewire\Payroll\MyPayslips;
use App\Livewire\Payroll\PayrollIndex;
use App\Livewire\Schedule\ScheduleRoster;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', Login::class)->name('login')->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Employees & Doctors Routes
    Route::get('/employees', EmployeeIndex::class)->name('employees.index');
    Route::get('/employees/{id}', EmployeeShow::class)->name('employees.show');
    Route::get('/doctors', DoctorIndex::class)->name('doctors.index');

    // Duty Rosters & Shifts
    Route::get('/schedules', ScheduleRoster::class)->name('schedules.index');

    // Leaves & Approvals
    Route::get('/leaves', LeaveIndex::class)->name('leaves.index');
    Route::get('/leaves/apply', LeaveApply::class)->name('leaves.apply');

    // Payroll & Payslips
    Route::get('/payroll', PayrollIndex::class)->name('payroll.index');
    Route::get('/payroll/my-payslips', MyPayslips::class)->name('payroll.my-payslips');
    Route::get('/payroll/payslips/{id}/print', PayrollPrintController::class.'@print')->name('payroll.payslips.print');

    // Document Compliance Archive
    Route::get('/documents', DocumentIndex::class)->name('documents.index');

    // System Audit Trail Explorer
    Route::get('/audit-logs', AuditLogIndex::class)->name('audit-logs.index');

    // Master Data Hub & Breakdown Sub-menus
    Route::get('/master', MasterIndex::class)->name('master.index');
    Route::get('/master/units', \App\Livewire\Master\UnitIndex::class)->name('master.units');
    Route::get('/master/positions', \App\Livewire\Master\PositionIndex::class)->name('master.positions');
    Route::get('/master/shifts', \App\Livewire\Master\ShiftIndex::class)->name('master.shifts');
    Route::get('/master/leave-types', \App\Livewire\Master\LeaveTypeIndex::class)->name('master.leave-types');
    Route::get('/master/geofences', \App\Livewire\Master\GeofenceIndex::class)->name('master.geofences');
    Route::get('/master/payroll-periods', \App\Livewire\Master\PayrollPeriodIndex::class)->name('master.payroll-periods');
    Route::get('/master/earnings', \App\Livewire\Master\EarningComponentIndex::class)->name('master.earnings');
    Route::get('/master/deductions', \App\Livewire\Master\DeductionComponentIndex::class)->name('master.deductions');
    Route::get('/master/bpjs', \App\Livewire\Master\BpjsIndex::class)->name('master.bpjs');
    Route::get('/master/pph21', \App\Livewire\Master\Pph21ParameterIndex::class)->name('master.pph21');

    // Organization Structure
    Route::get('/org/structure', \App\Livewire\Organization\OrgStructureIndex::class)->name('org.structure');

    // Executive & Operational Reports
    Route::get('/reports/employees', \App\Livewire\Report\EmployeeReportIndex::class)->name('reports.employees');
    Route::get('/reports/attendance', \App\Livewire\Report\AttendanceReportIndex::class)->name('reports.attendance');
    Route::get('/reports/leaves', \App\Livewire\Report\LeaveReportIndex::class)->name('reports.leaves');
    Route::get('/reports/payroll', \App\Livewire\Report\PayrollReportIndex::class)->name('reports.payroll');
    Route::get('/reports/doctors', \App\Livewire\Report\DoctorReportIndex::class)->name('reports.doctors');

    // System Settings & Hospital Config
    Route::get('/system/settings', \App\Livewire\System\SystemSettingIndex::class)->name('system.settings');

    // Attendance & Geofencing Routes
    Route::get('/attendance', AttendanceIndex::class)->name('attendance.index');
    Route::get('/attendance/clock', ClockInOut::class)->name('attendance.clock');

    Route::post('/logout', function (Request $request, AuditService $auditService) {
        $user = Auth::user();

        if ($user) {
            $auditService->log(
                action: 'auth.logout',
                auditable: $user,
                oldValues: [],
                newValues: ['ip' => $request->ip()],
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
