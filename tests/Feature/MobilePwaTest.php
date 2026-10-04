<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobilePwaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_manifest_webmanifest_exists_and_is_valid(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $this->assertFileExists($manifestPath);

        $content = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($content);
        $this->assertSame('SIM SDM RS Juwita', $content['name']);
        $this->assertSame('RS Juwita', $content['short_name']);
        $this->assertSame('standalone', $content['display']);
        $this->assertSame('/', $content['start_url']);
        $this->assertNotEmpty($content['icons']);

        foreach ($content['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_apple_touch_icon_exists(): void
    {
        $this->assertFileExists(public_path('assets/icons/apple-touch-icon.png'));
    }

    public function test_authenticated_layout_renders_pwa_and_mobile_app_elements(): void
    {
        $user = User::where('email', 'admin@rsjuwita.co.id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('manifest.webmanifest', false);
        $response->assertSee('name="apple-mobile-web-app-capable" content="yes"', false);
        $response->assertSee('viewport-fit=cover', false);
        $response->assertSee('apple-touch-icon', false);

        // Mobile bottom navigation bar exists
        $response->assertSee('Beranda');
        $response->assertSee('Presensi');
        $response->assertSee('Cuti');
        $response->assertSee('Buka Menu Lengkap');

        // Add to Home Screen (A2HS) helper prompt
        $response->assertSee('Pasang SIM SDM di Layar Utama');
    }

    public function test_guest_layout_renders_pwa_tags(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('name="apple-mobile-web-app-capable" content="yes"', false);
        $response->assertSee('viewport-fit=cover', false);
    }
}
