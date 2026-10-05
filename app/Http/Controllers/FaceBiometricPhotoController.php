<?php

namespace App\Http\Controllers;

use App\Models\FaceBiometricRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FaceBiometricPhotoController extends Controller
{
    /**
     * Stream an authorized face biometric registration photo from local private storage.
     */
    public function show(Request $request, FaceBiometricRegistration $registration): Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Authorization: HR managers / admins or the employee themselves
        $isSuperAdmin = $user->isSuperAdmin() || $user->hasRole('super_admin');
        $canManage = $user->can('employee.update') || $user->hasRole('hr_admin') || $user->hasRole('hr_manager');
        $isOwner = $registration->requested_by === $user->id || $registration->employee?->user_id === $user->id;

        if (! $isSuperAdmin && ! $canManage && ! $isOwner) {
            abort(403, 'Akses foto biometrik ditolak. Anda tidak memiliki izin untuk melihat berkas ini.');
        }

        if (empty($registration->photo_path)) {
            abort(404, 'Foto registrasi biometrik tidak ditemukan.');
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($registration->photo_path)) {
            abort(404, 'Berkas foto tidak ditemukan di penyimpanan server.');
        }

        $fileContent = $disk->get($registration->photo_path);
        $mimeType = $disk->mimeType($registration->photo_path) ?? 'image/jpeg';

        return response($fileContent, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="biometric_registration_'.$registration->id.'.jpg"',
            'Cache-Control' => 'private, no-store, must-revalidate',
        ]);
    }
}
