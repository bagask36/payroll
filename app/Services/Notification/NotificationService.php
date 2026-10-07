<?php

namespace App\Services\Notification;

use App\Models\Attendance;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Get all active notifications and action items for the given user.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getNotificationsForUser(User $user): Collection
    {
        $alerts = collect();

        // 1. Pending Leave Requests Alert
        try {
            if ($user->hasRole('supervisor') && $user->employee?->unit_id) {
                $pendingCount = LeaveRequest::where('status', 'pending_tier1')
                    ->whereHas('employee', fn ($q) => $q->where('unit_id', $user->employee->unit_id))
                    ->count();

                if ($pendingCount > 0) {
                    $alerts->push([
                        'id' => 'leave_pending_tier1',
                        'category' => 'cuti',
                        'category_label' => 'Cuti & Izin',
                        'type' => 'warning',
                        'title' => 'Verifikasi Cuti Staf Unit (Tier 1)',
                        'message' => "Ada {$pendingCount} pengajuan cuti staf unit Anda yang menunggu peninjauan dan persetujuan supervisor.",
                        'time' => 'Perlu tindakan',
                        'date' => now()->translatedFormat('d M Y, H:i'),
                        'url' => route('leaves.index'),
                        'action_label' => 'Buka Antrean Cuti',
                        'icon' => 'document-magnifying-glass',
                        'badge' => "{$pendingCount} Pengajuan",
                        'badge_color' => 'amber',
                        'is_urgent' => true,
                    ]);
                }
            } elseif ($user->hasRole('super_admin') || $user->hasRole('hr_admin') || $user->hasRole('hr_manager')) {
                $pendingCount = LeaveRequest::whereIn('status', ['pending_tier1', 'pending_tier2'])->count();

                if ($pendingCount > 0) {
                    $alerts->push([
                        'id' => 'leave_pending_admin',
                        'category' => 'cuti',
                        'category_label' => 'Cuti & Izin',
                        'type' => 'warning',
                        'title' => 'Antrean Persetujuan Cuti Karyawan',
                        'message' => "Ada {$pendingCount} pengajuan cuti karyawan menunggu verifikasi HR / Manajemen rumah sakit.",
                        'time' => 'Perlu tindakan',
                        'date' => now()->translatedFormat('d M Y, H:i'),
                        'url' => route('leaves.index'),
                        'action_label' => 'Kelola Persetujuan Cuti',
                        'icon' => 'document-magnifying-glass',
                        'badge' => "{$pendingCount} Antrean",
                        'badge_color' => 'amber',
                        'is_urgent' => true,
                    ]);
                }
            } elseif ($user->employee) {
                $myPendingLeaves = LeaveRequest::where('employee_id', $user->employee->id)
                    ->whereIn('status', ['pending_tier1', 'pending_tier2'])
                    ->count();

                if ($myPendingLeaves > 0) {
                    $alerts->push([
                        'id' => 'my_pending_leaves',
                        'category' => 'cuti',
                        'category_label' => 'Cuti & Izin',
                        'type' => 'info',
                        'title' => 'Pengajuan Cuti Sedang Diproses',
                        'message' => "Anda memiliki {$myPendingLeaves} pengajuan cuti aktif yang sedang dalam proses verifikasi berjenjang.",
                        'time' => 'Dalam proses',
                        'date' => now()->translatedFormat('d M Y, H:i'),
                        'url' => route('leaves.index'),
                        'action_label' => 'Lihat Status Cuti',
                        'icon' => 'calendar',
                        'badge' => 'Menunggu Verifikasi',
                        'badge_color' => 'blue',
                        'is_urgent' => false,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // gracefully skip on database mismatch
        }

        // 2. Doctor Credential Alerts (STR / SIP Expiring)
        try {
            if ($user->hasRole('super_admin') || $user->hasRole('hr_admin') || $user->hasRole('hr_manager')) {
                $expiringDoctors = Doctor::where('status', 'active')
                    ->where(function ($q) {
                        $q->where('str_valid_until', '<=', now()->addDays(30))
                            ->orWhere('sip_valid_until', '<=', now()->addDays(30));
                    })->count();

                if ($expiringDoctors > 0) {
                    $alerts->push([
                        'id' => 'doctor_credentials_admin',
                        'category' => 'medis',
                        'category_label' => 'Tenaga Medis',
                        'type' => 'danger',
                        'title' => 'Kepatuhan Izin Praktik Medis Dokter',
                        'message' => "{$expiringDoctors} dokter memiliki masa berlaku STR atau SIP yang telah kedaluwarsa atau mendekati batas akhir (< 30 hari).",
                        'time' => 'Kredensial wajib',
                        'date' => now()->translatedFormat('d M Y, H:i'),
                        'url' => route('doctors.index'),
                        'action_label' => 'Periksa Berkas Dokter',
                        'icon' => 'exclamation-triangle',
                        'badge' => "{$expiringDoctors} Dokter Perlu Update",
                        'badge_color' => 'rose',
                        'is_urgent' => true,
                    ]);
                }
            } elseif ($user->hasRole('doctor')) {
                $myDoc = Doctor::where('user_id', $user->id)->first();
                if ($myDoc) {
                    $strExp = $myDoc->str_valid_until && Carbon::parse($myDoc->str_valid_until)->diffInDays(now(), false) >= -30;
                    $sipExp = $myDoc->sip_valid_until && Carbon::parse($myDoc->sip_valid_until)->diffInDays(now(), false) >= -30;

                    if ($strExp || $sipExp) {
                        $alerts->push([
                            'id' => 'my_doctor_credentials',
                            'category' => 'medis',
                            'category_label' => 'Tenaga Medis',
                            'type' => 'danger',
                            'title' => 'Peringatan Masa Berlaku STR / SIP',
                            'message' => 'Masa berlaku izin praktik (STR atau SIP) Anda mendekati batas akhir atau telah habis. Harap segera serahkan pembaruan berkas ke Bagian HR / Komite Medis.',
                            'time' => 'Wajib diperbarui',
                            'date' => now()->translatedFormat('d M Y, H:i'),
                            'url' => route('doctors.index'),
                            'action_label' => 'Tinjau Profil Medis',
                            'icon' => 'exclamation-triangle',
                            'badge' => 'Segera Perbarui',
                            'badge_color' => 'rose',
                            'is_urgent' => true,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // gracefully skip
        }

        // 3. Employee Contract Renewal Alerts (PKWT / Probation ending within 30 days)
        try {
            if ($user->hasRole('super_admin') || $user->hasRole('hr_admin') || $user->hasRole('hr_manager')) {
                $expiringContracts = EmployeeEmployment::where('is_active', true)
                    ->whereNotNull('end_date')
                    ->whereBetween('end_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->count();

                if ($expiringContracts > 0) {
                    $alerts->push([
                        'id' => 'contract_renewal_admin',
                        'category' => 'kepegawaian',
                        'category_label' => 'Kepegawaian',
                        'type' => 'warning',
                        'title' => 'Masa Berlaku Perjanjian Kerja (PKWT)',
                        'message' => "Ada {$expiringContracts} karyawan dengan kontrak kerja (PKWT) yang akan berakhir dalam 30 hari ke depan.",
                        'time' => 'Evaluasi kontrak',
                        'date' => now()->translatedFormat('d M Y, H:i'),
                        'url' => route('employees.index'),
                        'action_label' => 'Lihat Data Karyawan',
                        'icon' => 'identification',
                        'badge' => "{$expiringContracts} Kontrak Berakhir",
                        'badge_color' => 'amber',
                        'is_urgent' => true,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // gracefully skip
        }

        // 4. Attendance Check-in Reminder
        try {
            if ($user->employee) {
                $checkedIn = Attendance::where('employee_id', $user->employee->id)
                    ->whereDate('date', now()->toDateString())
                    ->whereNotNull('clock_in')
                    ->exists();

                if (! $checkedIn && now()->hour >= 7) {
                    $alerts->push([
                        'id' => 'attendance_clock_in_reminder',
                        'category' => 'presensi',
                        'category_label' => 'Presensi & Jadwal',
                        'type' => 'info',
                        'title' => 'Pengingat Presensi Hari Ini',
                        'message' => 'Anda belum melakukan pencatatan kehadiran (clock-in) hari ini. Pastikan melakukan presensi saat berada dalam radius geofence rumah sakit.',
                        'time' => 'Hari ini',
                        'date' => now()->translatedFormat('d M Y, H:i'),
                        'url' => route('attendance.index'),
                        'action_label' => 'Catat Kehadiran',
                        'icon' => 'clock',
                        'badge' => 'Presensi Masuk',
                        'badge_color' => 'blue',
                        'is_urgent' => false,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // gracefully skip
        }

        // 5. Finalized Payroll Slip Notification
        try {
            if ($user->employee) {
                $latestRun = PayrollRun::where('status', 'finalized')->latest('run_date')->first();

                if ($latestRun && $latestRun->created_at->diffInDays(now()) <= 14) {
                    $alerts->push([
                        'id' => 'payslip_published',
                        'category' => 'payroll',
                        'category_label' => 'Payroll & Gaji',
                        'type' => 'success',
                        'title' => 'Slip Gaji Resmi Telah Diterbitkan',
                        'message' => "Slip gaji periode {$latestRun->period?->name} telah selesai diverifikasi dan difinalisasi oleh bagian keuangan. Anda dapat mengunduh atau mencetak dokumen payslip.",
                        'time' => 'Terbit',
                        'date' => $latestRun->created_at->translatedFormat('d M Y, H:i'),
                        'url' => route('payroll.my-payslips'),
                        'action_label' => 'Buka Slip Gaji',
                        'icon' => 'receipt-percent',
                        'badge' => 'Slip Gaji Tersedia',
                        'badge_color' => 'emerald',
                        'is_urgent' => false,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // gracefully skip
        }

        // 6. Hospital Operations & Infrastructure Status
        $alerts->push([
            'id' => 'hospital_system_status',
            'category' => 'sistem',
            'category_label' => 'Sistem & Operasional',
            'type' => 'neutral',
            'title' => 'Sistem RS Permata Bunda Aktif',
            'message' => 'Layanan pemantauan geofence, validasi shift dinas medis 24 jam, dan sinkronisasi presensi operasional berjalan normal tanpa kendala.',
            'time' => now()->translatedFormat('H:i') . ' WIB',
            'date' => now()->translatedFormat('d M Y, H:i'),
            'url' => route('dashboard'),
            'action_label' => 'Buka Dashboard',
            'icon' => 'shield-check',
            'badge' => 'Siaga 24 Jam',
            'badge_color' => 'emerald',
            'is_urgent' => false,
        ]);

        return $alerts;
    }
}
