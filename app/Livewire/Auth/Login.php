<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Masuk - RS Juwita')]
class Login extends Component
{
    #[Rule(['required', 'string', 'email'])]
    public string $email = '';

    #[Rule(['required', 'string'])]
    public string $password = '';

    public bool $remember = false;

    public function login(AuditService $auditService): void
    {
        $this->validate();

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $user = User::where('email', $this->email)->first();

        if (! $user || ! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 300);

            $auditService->log(
                action: 'auth.login_failed',
                auditable: $user,
                oldValues: [],
                newValues: ['email' => $this->email, 'ip' => request()->ip()],
            );

            throw ValidationException::withMessages([
                'email' => __('Kredensial yang diberikan tidak cocok dengan data kami.'),
            ]);
        }

        if (! $user->is_active) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            $auditService->log(
                action: 'auth.login_blocked_inactive',
                auditable: $user,
                oldValues: [],
                newValues: ['email' => $this->email, 'ip' => request()->ip()],
            );

            throw ValidationException::withMessages([
                'email' => __('Akun Anda dinonaktifkan. Silakan hubungi bagian SDM RS Juwita.'),
            ]);
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        $auditService->log(
            action: 'auth.login_success',
            auditable: $user,
            oldValues: [],
            newValues: ['ip' => request()->ip(), 'user_agent' => request()->userAgent()],
        );

        $this->redirectIntended(default: route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
