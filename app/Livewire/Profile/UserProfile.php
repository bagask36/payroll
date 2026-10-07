<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Profil Saya & Kelola Akun - RS Permata Bunda')]
class UserProfile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    // Password Update State
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    // Avatar State
    public $avatarUpload = null;

    // Feedback Messages
    public ?string $profileFeedback = null;

    public ?string $profileError = null;

    public ?string $passwordFeedback = null;

    public ?string $passwordError = null;

    public ?string $avatarFeedback = null;

    public ?string $avatarError = null;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->name = (string) $user->name;
            $this->email = (string) $user->email;
        }
    }

    public function updateProfileInformation(AuditService $auditService): void
    {
        $this->resetFeedback();

        /** @var User $user */
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ], [
            'name.required' => 'Nama pengguna wajib diisi.',
            'name.max' => 'Nama pengguna tidak boleh lebih dari 255 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan oleh akun lain.',
        ]);

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        $user->update($validated);

        $auditService->log(
            action: 'user.profile_updated',
            auditable: $user,
            oldValues: $oldValues,
            newValues: $validated
        );

        $this->profileFeedback = 'Informasi akun profil Anda berhasil diperbarui.';
    }

    public function updatePassword(AuditService $auditService): void
    {
        $this->resetFeedback();

        /** @var User $user */
        $user = Auth::user();

        $this->validate([
            'currentPassword' => ['required', 'string', 'current_password'],
            'newPassword' => ['required', 'string', 'min:8', 'different:currentPassword', 'same:newPasswordConfirmation'],
            'newPasswordConfirmation' => ['required', 'string'],
        ], [
            'currentPassword.required' => 'Kata sandi saat ini wajib diisi.',
            'currentPassword.current_password' => 'Kata sandi saat ini tidak sesuai dengan akun Anda.',
            'newPassword.required' => 'Kata sandi baru wajib diisi.',
            'newPassword.min' => 'Kata sandi baru minimal harus 8 karakter.',
            'newPassword.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
            'newPassword.same' => 'Konfirmasi kata sandi baru tidak cocok.',
            'newPasswordConfirmation.required' => 'Konfirmasi kata sandi baru wajib diisi.',
        ]);

        $user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $auditService->log(
            action: 'user.password_changed',
            auditable: $user,
            oldValues: [],
            newValues: ['changed_at' => now()->toIso8601String()]
        );

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';

        $this->passwordFeedback = 'Kata sandi akun Anda berhasil diperbarui dengan aman.';
    }

    public function uploadAvatar(AuditService $auditService): void
    {
        $this->resetFeedback();

        $this->validate([
            'avatarUpload' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'avatarUpload.required' => 'Silakan pilih berkas foto terlebih dahulu.',
            'avatarUpload.image' => 'Berkas harus berupa gambar.',
            'avatarUpload.mimes' => 'Format foto harus berupa JPEG, PNG, atau WebP.',
            'avatarUpload.max' => 'Ukuran foto maksimal adalah 2MB.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        // Delete previous avatar file if exists
        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $this->avatarUpload->store('avatars', 'public');
        $oldPath = $user->profile_photo_path;

        $user->update(['profile_photo_path' => $path]);

        $auditService->log(
            action: 'user.avatar_updated',
            auditable: $user,
            oldValues: ['profile_photo_path' => $oldPath],
            newValues: ['profile_photo_path' => $path]
        );

        $this->avatarUpload = null;
        $this->avatarFeedback = 'Foto profil akun Anda berhasil diperbarui.';
    }

    public function deleteAvatar(AuditService $auditService): void
    {
        $this->resetFeedback();

        /** @var User $user */
        $user = Auth::user();

        if (! $user->profile_photo_path) {
            return;
        }

        $oldPath = $user->profile_photo_path;
        if (Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $user->update(['profile_photo_path' => null]);

        $auditService->log(
            action: 'user.avatar_deleted',
            auditable: $user,
            oldValues: ['profile_photo_path' => $oldPath],
            newValues: ['profile_photo_path' => null]
        );

        $this->avatarFeedback = 'Foto profil akun Anda berhasil dihapus.';
    }

    protected function resetFeedback(): void
    {
        $this->profileFeedback = null;
        $this->profileError = null;
        $this->passwordFeedback = null;
        $this->passwordError = null;
        $this->avatarFeedback = null;
        $this->avatarError = null;
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();

        return view('livewire.profile.user-profile', [
            'user' => $user->loadMissing(['roles.permissions', 'employee.unit', 'employee.position', 'doctor']),
        ]);
    }
}
