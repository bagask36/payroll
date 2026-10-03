<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_audit_log_with_actor_and_metadata(): void
    {
        $user = User::factory()->create();
        $auditService = app(AuditService::class);

        $log = $auditService->log(
            action: 'employee.created',
            auditable: $user,
            oldValues: null,
            newValues: ['name' => 'Dr. Juwita', 'role' => 'Doctor'],
            userId: $user->id
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'action' => 'employee.created',
            'user_id' => $user->id,
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->id,
        ]);

        $this->assertSame('Dr. Juwita', $log->new_values['name']);
        $this->assertTrue($log->user->is($user));
        $this->assertTrue($log->auditable->is($user));
    }

    public function test_it_redacts_sensitive_credentials_and_biometric_data(): void
    {
        $auditService = app(AuditService::class);

        $payload = [
            'username' => 'hr_admin',
            'password' => 'super-secret-password',
            'password_confirmation' => 'super-secret-password',
            'api_token' => 'Bearer 1234567890',
            'biometric_template' => 'raw_binary_vector_data',
            'face_embedding' => [0.12, 0.45, 0.99],
            'safe_field' => 'Hospital Juwita HR',
        ];

        $sanitized = $auditService->sanitizePayload($payload);

        $this->assertSame('[REDACTED]', $sanitized['password']);
        $this->assertSame('[REDACTED]', $sanitized['password_confirmation']);
        $this->assertSame('[REDACTED]', $sanitized['api_token']);
        $this->assertSame('[REDACTED]', $sanitized['biometric_template']);
        $this->assertSame('[REDACTED]', $sanitized['face_embedding']);
        $this->assertSame('Hospital Juwita HR', $sanitized['safe_field']);
    }
}
