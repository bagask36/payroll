<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class DoctorReportTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'doctor_reports_table';

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('laporan-tenaga-medis-rs-juwita-'.now()->format('Y-m-d'))
                ->type(Exportable::TYPE_CSV),
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage(perPage: 10, perPageValues: [10, 25, 50, 100])
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Doctor::query()->with(['employee']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', fn (Doctor $doc) => '<span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($doc->name).'</span>')
            ->add('specialization', fn (Doctor $doc) => '<span class="text-xs text-zinc-600 dark:text-zinc-400">'.e($doc->specialization ?? 'Dokter Umum').'</span>')
            ->add('category_badge', function (Doctor $doc) {
                if ($doc->employee_id !== null) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Dokter Tetap (Organik)</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">Dokter Tamu (Mitra)</span>';
            })
            ->add('str_display', function (Doctor $doc) {
                $expiry = $doc->str_valid_until ? $doc->str_valid_until->format('d/m/Y') : '-';

                return '<div><div class="font-mono text-xs text-zinc-800 dark:text-zinc-200">'.e($doc->str_number ?? '-').'</div><div class="text-2xs text-zinc-400">s/d '.e($expiry).'</div></div>';
            })
            ->add('sip_display', function (Doctor $doc) {
                $expiry = $doc->sip_valid_until ? $doc->sip_valid_until->format('d/m/Y') : '-';

                return '<div><div class="font-mono text-xs text-zinc-800 dark:text-zinc-200">'.e($doc->sip_number ?? '-').'</div><div class="text-2xs text-zinc-400">s/d '.e($expiry).'</div></div>';
            })
            ->add('status_badge', function (Doctor $doc) {
                if ($doc->status === 'active') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">Nonaktif</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Nama Dokter', 'name', 'doctors.name')
                ->searchable()
                ->sortable(),

            Column::make('Spesialisasi', 'specialization', 'doctors.specialization')
                ->searchable()
                ->sortable(),

            Column::make('Kategori Praktik', 'category_badge'),

            Column::make('Lisensi STR', 'str_display'),

            Column::make('Izin Praktik SIP', 'sip_display'),

            Column::make('Status', 'status_badge', 'doctors.status')
                ->sortable(),

            Column::action('Opsi'),
        ];
    }

    public function actions(Doctor $row): array
    {
        return [
            Button::add('view')
                ->slot('Kelola')
                ->route('doctors.index', [])
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer'),
        ];
    }
}
