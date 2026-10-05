<?php

use App\Http\Controllers\AttendancePhotoController;
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
use App\Livewire\Master\BpjsIndex;
use App\Livewire\Master\DeductionComponentIndex;
use App\Livewire\Master\EarningComponentIndex;
use App\Livewire\Master\GeofenceIndex;
use App\Livewire\Master\LeaveTypeIndex;
use App\Livewire\Master\MasterIndex;
use App\Livewire\Master\PayrollPeriodIndex;
use App\Livewire\Master\PositionIndex;
use App\Livewire\Master\Pph21ParameterIndex;
use App\Livewire\Master\ShiftIndex;
use App\Livewire\Master\UnitIndex;
use App\Livewire\Master\WarningLetterTypeIndex;
use App\Livewire\Organization\OrgStructureIndex;
use App\Livewire\Payroll\MyPayslips;
use App\Livewire\Payroll\PayrollIndex;
use App\Livewire\Report\AttendanceReportIndex;
use App\Livewire\Report\DoctorReportIndex;
use App\Livewire\Report\EmployeeReportIndex;
use App\Livewire\Report\LeaveReportIndex;
use App\Livewire\Report\PayrollReportIndex;
use App\Livewire\Report\Pph21ReportIndex;
use App\Livewire\Schedule\ScheduleRoster;
use App\Livewire\System\SystemSettingIndex;
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
    Route::get('/master/units', UnitIndex::class)->name('master.units');
    Route::get('/master/positions', PositionIndex::class)->name('master.positions');
    Route::get('/master/shifts', ShiftIndex::class)->name('master.shifts');
    Route::get('/master/leave-types', LeaveTypeIndex::class)->name('master.leave-types');
    Route::get('/master/geofences', GeofenceIndex::class)->name('master.geofences');
    Route::get('/master/payroll-periods', PayrollPeriodIndex::class)->name('master.payroll-periods');
    Route::get('/master/earnings', EarningComponentIndex::class)->name('master.earnings');
    Route::get('/master/deductions', DeductionComponentIndex::class)->name('master.deductions');
    Route::get('/master/bpjs', BpjsIndex::class)->name('master.bpjs');
    Route::get('/master/pph21', Pph21ParameterIndex::class)->name('master.pph21');
    Route::get('/master/warning-letter-types', WarningLetterTypeIndex::class)->name('master.warning-letter-types');

    // Organization Structure
    Route::get('/org/structure', OrgStructureIndex::class)->name('org.structure');

    // Executive & Operational Reports
    Route::get('/reports/employees', EmployeeReportIndex::class)->name('reports.employees');
    Route::get('/reports/attendance', AttendanceReportIndex::class)->name('reports.attendance');
    Route::get('/reports/leaves', LeaveReportIndex::class)->name('reports.leaves');
    Route::get('/reports/payroll', PayrollReportIndex::class)->name('reports.payroll');
    Route::get('/reports/pph21', Pph21ReportIndex::class)->name('reports.pph21');
    Route::get('/reports/doctors', DoctorReportIndex::class)->name('reports.doctors');

    // System Settings & Hospital Config
    Route::get('/system/settings', SystemSettingIndex::class)->name('system.settings');

    // Attendance & Geofencing Routes
    Route::get('/attendance', AttendanceIndex::class)->name('attendance.index');
    Route::get('/attendance/clock', ClockInOut::class)->name('attendance.clock');
    Route::get('/attendance/events/{event}/photo', [AttendancePhotoController::class, 'show'])->name('attendance.photo');

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
