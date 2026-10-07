<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Masuk - SIM SDM RS Permata Bunda')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public string $captcha = '';

    public string $captchaKey = '';

    public bool $remember = false;

    public function mount(): void
    {
        $this->reloadCaptcha();
    }

    public function reloadCaptcha(): void
    {
        $this->captcha = '';
        $this->captchaKey = Str::random(8);
    }

    protected function rules(): array
    {
        $rules = [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];

        if (! config('captcha.disable', false)) {
            $rules['captcha'] = ['required', 'string', 'captcha'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'email.required' => __('Alamat email wajib diisi.'),
            'email.email' => __('Format alamat email tidak valid.'),
            'password.required' => __('Kata sandi wajib diisi.'),
            'captcha.required' => __('Kode captcha keamanan wajib diisi.'),
            'captcha.captcha' => __('Kode captcha tidak cocok atau telah kedaluwarsa. Silakan coba lagi.'),
        ];
    }

    public function login(AuditService $auditService): void
    {
        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->reloadCaptcha();
            throw $e;
        }

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->reloadCaptcha();
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

            $this->reloadCaptcha();

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

            $this->reloadCaptcha();

            throw ValidationException::withMessages([
                'email' => __('Akun Anda dinonaktifkan. Silakan hubungi bagian SDM RS Permata Bunda.'),
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
