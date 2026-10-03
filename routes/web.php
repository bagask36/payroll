<?php

use App\Livewire\Attendance\AttendanceIndex;
use App\Livewire\Attendance\ClockInOut;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Doctor\DoctorIndex;
use App\Livewire\Employee\EmployeeIndex;
use App\Livewire\Employee\EmployeeShow;
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
