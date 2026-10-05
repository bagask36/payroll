<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEvent;
use App\Services\Document\GoogleDriveStorageProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttendancePhotoController extends Controller
{
    /**
     * Stream an authorized attendance photo from Google Drive or local temporary storage.
     */
    public function show(Request $request, AttendanceEvent $event, GoogleDriveStorageProvider $gdriveProvider): Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $attendance = $event->attendance;
        if (! $attendance) {
            abort(404, 'Attendance record not found.');
        }

        // Authorization checks (docs/05-roles-permissions.md)
        $isSuperAdmin = $user->isSuperAdmin() || $user->hasRole('super_admin');
        $hasGlobalPermission = $user->can('attendance.view_all') || $user->hasRole('hr_admin') || $user->hasRole('hr_manager') || $user->hasRole('payroll');
        $isOwnPhoto = ($attendance->employee_id && $user->employee?->id === $attendance->employee_id)
            || ($attendance->doctor_id && $user->doctor?->id === $attendance->doctor_id);

        $isUnitSupervisor = false;
        if (! $hasGlobalPermission && ! $isOwnPhoto && $user->can('attendance.view_team')) {
            $supervisorUnitId = $user->employee?->unit_id;
            $targetUnitId = $attendance->employee?->unit_id;
            if ($supervisorUnitId && $targetUnitId && $supervisorUnitId === $targetUnitId) {
                $isUnitSupervisor = true;
            }
        }

        if (! $isSuperAdmin && ! $hasGlobalPermission && ! $isOwnPhoto && ! $isUnitSupervisor) {
            abort(403, 'Akses foto presensi ditolak. Anda tidak memiliki izin untuk melihat bukti presensi ini.');
        }

        if (empty($event->photo_path)) {
            abort(404, 'Foto presensi tidak tersedia.');
        }

        // Case 1: Photo stored in Google Drive
        if ($event->storage_provider === 'google_drive') {
            $content = $gdriveProvider->get($event->photo_path);

            if ($content !== null) {
                return response($content, 200, [
                    'Content-Type' => 'image/jpeg',
                    'Content-Disposition' => 'inline; filename="attendance_'.$event->id.'.jpg"',
                    'Cache-Control' => 'private, max-age=86400',
                ]);
            }

            // Fallback: If still testing/mock or Google Drive download returns null, check if local file still exists
            $disk = Storage::disk('public')->exists($event->photo_path) ? 'public' : 'local';
            if (Storage::disk($disk)->exists($event->photo_path)) {
                return Storage::disk($disk)->response($event->photo_path);
            }

            // Return a clean fallback placeholder SVG if the file is in mock mode
            $placeholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">'
                .'<rect width="300" height="300" fill="#18181b"/>'
                .'<circle cx="150" cy="110" r="50" fill="#3f3f46"/>'
                .'<path d="M70,240 C70,185 110,175 150,175 C190,175 230,185 230,240 Z" fill="#3f3f46"/>'
                .'<text x="150" y="275" font-family="sans-serif" font-size="12" fill="#a1a1aa" text-anchor="middle">Google Drive Cloud Storage</text>'
                .'</svg>';

            return response($placeholderSvg, 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'no-cache',
            ]);
        }

        // Case 2: Photo stored in local temporary storage
        $disk = Storage::disk('public')->exists($event->photo_path) ? 'public' : 'local';

        if (Storage::disk($disk)->exists($event->photo_path)) {
            return Storage::disk($disk)->response($event->photo_path);
        }

        abort(404, 'File foto presensi tidak ditemukan pada storage.');
    }
}
