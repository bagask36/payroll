<?php

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
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
