<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_screen(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('SIM SDM RS Permata Bunda');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_active_user_can_authenticate_and_redirect_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'perawat@juwitahospital.com',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'perawat@juwitahospital.com')
            ->set('password', 'Secret123!')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_success',
            'auditable_id' => $user->id,
        ]);
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'nonaktif@juwitahospital.com',
            'password' => Hash::make('Secret123!'),
            'is_active' => false,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'nonaktif@juwitahospital.com')
            ->set('password', 'Secret123!')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_blocked_inactive',
            'auditable_id' => $user->id,
        ]);
    }

    public function test_invalid_credentials_fail_and_record_audit_log(): void
    {
        $user = User::factory()->create([
            'email' => 'dokter@juwitahospital.com',
            'password' => Hash::make('PasswordBenar!'),
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'dokter@juwitahospital.com')
            ->set('password', 'PasswordSalah!')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_failed',
            'auditable_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.logout',
            'auditable_id' => $user->id,
        ]);
    }

    public function test_login_fails_when_captcha_is_invalid(): void
    {
        config(['captcha.disable' => false]);

        User::factory()->create([
            'email' => 'perawat@juwitahospital.com',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'perawat@juwitahospital.com')
            ->set('password', 'Secret123!')
            ->set('captcha', 'wrong-code')
            ->call('login')
            ->assertHasErrors(['captcha']);

        $this->assertGuest();
    }

    public function test_login_fails_when_captcha_is_empty_when_enabled(): void
    {
        config(['captcha.disable' => false]);

        Livewire::test(Login::class)
            ->set('email', 'perawat@juwitahospital.com')
            ->set('password', 'Secret123!')
            ->set('captcha', '')
            ->call('login')
            ->assertHasErrors(['captcha']);
    }

    public function test_captcha_can_be_reloaded_and_resets_input(): void
    {
        $component = Livewire::test(Login::class);
        $initialKey = $component->get('captchaKey');

        $component->set('captcha', 'old-value');
        $component->call('reloadCaptcha');

        $this->assertNotEquals($initialKey, $component->get('captchaKey'));
        $this->assertSame('', $component->get('captcha'));
    }
}
