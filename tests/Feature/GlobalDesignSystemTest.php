<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class GlobalDesignSystemTest extends TestCase
{
    public function test_page_header_component_renders_correctly(): void
    {
        $rendered = Blade::render(
            '<x-page-header title="Manajemen Staf" subtitle="Kelola direktori staf dan karyawan">'
            . '<x-slot:actions><button>Tambah</button></x-slot:actions>'
            . '</x-page-header>'
        );

        $this->assertStringContainsString('app-page-header', $rendered);
        $this->assertStringContainsString('Manajemen Staf', $rendered);
        $this->assertStringContainsString('Kelola direktori staf dan karyawan', $rendered);
        $this->assertStringContainsString('<button>Tambah</button>', $rendered);
    }

    public function test_flash_message_component_renders_alert_types(): void
    {
        $success = Blade::render('<x-flash-message message="Operasi sukses!" type="success" />');
        $this->assertStringContainsString('app-alert-success', $success);
        $this->assertStringContainsString('Operasi sukses!', $success);

        $error = Blade::render('<x-flash-message message="Terjadi galat!" type="danger" />');
        $this->assertStringContainsString('app-alert-danger', $error);
        $this->assertStringContainsString('Terjadi galat!', $error);

        $empty = Blade::render('<x-flash-message :message="null" />');
        $this->assertEmpty(trim($empty));
    }

    public function test_table_card_component_renders_wrapper(): void
    {
        $rendered = Blade::render('<x-table-card><div>Tabel Data</div></x-table-card>');

        $this->assertStringContainsString('app-table-card', $rendered);
        $this->assertStringContainsString('Tabel Data', $rendered);
    }

    public function test_empty_state_component_renders(): void
    {
        $rendered = Blade::render(
            '<x-empty-state title="Belum Ada Transaksi" description="Silakan buat transaksi baru">'
            . '<button>Buat Sekarang</button>'
            . '</x-empty-state>'
        );

        $this->assertStringContainsString('Belum Ada Transaksi', $rendered);
        $this->assertStringContainsString('Silakan buat transaksi baru', $rendered);
        $this->assertStringContainsString('Buat Sekarang', $rendered);
    }
}
