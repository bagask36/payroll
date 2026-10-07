<?php

namespace App\Livewire\System;

use App\Models\SystemSetting;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pengaturan Sistem & Faskes - RS Permata Bunda')]
class SystemSettingIndex extends Component
{
    // Hospital Profile
    public string $hospitalName = '';

    public string $hospitalAddress = '';

    public string $hospitalPhone = '';

    public string $hospitalEmail = '';

    public string $hospitalFaskesCode = '';

    public string $hospitalNpwp = '';

    // Attendance Policy
    public int $lateGracePeriodMinutes = 15;

    public int $defaultGeofenceRadius = 100;

    // Security & Auth
    public int $sessionTimeoutMinutes = 120;

    public bool $biometricLivenessRequired = true;

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('system.config_view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke pengaturan sistem ditolak.');
        }

        $this->hospitalName = SystemSetting::get('hospital_name', 'RS Permata Bunda');
        $this->hospitalAddress = SystemSetting::get('hospital_address', 'Jl. Mayor M. Hasibuan No. 78, Bekasi Timur');
        $this->hospitalPhone = SystemSetting::get('hospital_phone', '(021) 8816654');
        $this->hospitalEmail = SystemSetting::get('hospital_email', 'sdm@rsjuwita.co.id');
        $this->hospitalFaskesCode = SystemSetting::get('hospital_faskes_code', '3275012');
        $this->hospitalNpwp = SystemSetting::get('hospital_npwp', '01.889.345.2-407.000');

        $this->lateGracePeriodMinutes = (int) SystemSetting::get('late_grace_period_minutes', '15');
        $this->defaultGeofenceRadius = (int) SystemSetting::get('default_geofence_radius', '100');

        $this->sessionTimeoutMinutes = (int) SystemSetting::get('session_timeout_minutes', '120');
        $this->biometricLivenessRequired = (bool) (int) SystemSetting::get('biometric_liveness_required', '1');
    }

    public function save(AuditService $auditService): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('system.config_update') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_manager'))) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengubah pengaturan sistem.');
        }

        $this->validate([
            'hospitalName' => 'required|string|max:100',
            'hospitalAddress' => 'required|string|max:255',
            'hospitalPhone' => 'required|string|max:50',
            'hospitalEmail' => 'required|email|max:100',
            'lateGracePeriodMinutes' => 'required|integer|min:0|max:120',
            'defaultGeofenceRadius' => 'required|integer|min:10|max:1000',
            'sessionTimeoutMinutes' => 'required|integer|min:15|max:1440',
        ]);

        SystemSetting::set('hospital_name', $this->hospitalName, 'general', 'Nama faskes rumah sakit');
        SystemSetting::set('hospital_address', $this->hospitalAddress, 'general', 'Alamat faskes');
        SystemSetting::set('hospital_phone', $this->hospitalPhone, 'general', 'Telepon hotline faskes');
        SystemSetting::set('hospital_email', $this->hospitalEmail, 'general', 'Email faskes');
        SystemSetting::set('hospital_faskes_code', $this->hospitalFaskesCode, 'general', 'Kode registrasi faskes');
        SystemSetting::set('hospital_npwp', $this->hospitalNpwp, 'general', 'NPWP faskes');

        SystemSetting::set('late_grace_period_minutes', (string) $this->lateGracePeriodMinutes, 'attendance', 'Toleransi keterlambatan menit');
        SystemSetting::set('default_geofence_radius', (string) $this->defaultGeofenceRadius, 'attendance', 'Radius geofence meter');

        SystemSetting::set('session_timeout_minutes', (string) $this->sessionTimeoutMinutes, 'security', 'Sesi timeout menit');
        SystemSetting::set('biometric_liveness_required', $this->biometricLivenessRequired ? '1' : '0', 'security', 'Liveness check AI');

        $auditService->log(
            action: 'system_settings.updated',
            auditable: $user,
            newValues: [
                'hospital_name' => $this->hospitalName,
                'grace_period' => $this->lateGracePeriodMinutes,
                'geofence_radius' => $this->defaultGeofenceRadius,
            ]
        );

        $this->feedbackMessage = 'Pengaturan sistem & parameter operasional rumah sakit berhasil disimpan!';
    }

    public function render()
    {
        return view('livewire.system.system-setting-index');
    }
}
