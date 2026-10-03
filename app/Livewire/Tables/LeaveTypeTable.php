<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class LeaveTypeTable extends PowerGridComponent
{
    public string $tableName = 'leave_types_table';

    public function setUp(): array
    {
        return [
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
        return LeaveType::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code', fn (LeaveType $lt) => '<span class="font-mono font-semibold text-xs text-zinc-800 dark:text-zinc-200">'.e($lt->code).'</span>')
            ->add('name', fn (LeaveType $lt) => '<span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($lt->name).'</span>')
            ->add('days_count', fn (LeaveType $lt) => '<span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">'.$lt->days_count.' Hari / Tahun</span>')
            ->add('deduct_badge', function (LeaveType $lt) {
                if ($lt->is_deduct_annual) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Memotong Saldo</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-600 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700">Khusus / Tidak Memotong</span>';
            })
            ->add('attachment_badge', function (LeaveType $lt) {
                if ($lt->requires_attachment) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">Wajib Surat/Berkas</span>';
                }

                return '<span class="text-zinc-400 text-xs">Opsional</span>';
            })
            ->add('status_badge', function (LeaveType $lt) {
                if ($lt->is_active) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">Nonaktif</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Kode Cuti', 'code', 'leave_types.code')
                ->searchable()
                ->sortable(),

            Column::make('Nama Jenis Cuti', 'name', 'leave_types.name')
                ->searchable()
                ->sortable(),

            Column::make('Alokasi Dasar', 'days_count', 'leave_types.days_count')
                ->sortable(),

            Column::make('Aturan Kuota Tahunan', 'deduct_badge'),

            Column::make('Lampiran Bukti', 'attachment_badge'),

            Column::make('Status', 'status_badge', 'leave_types.is_active')
                ->sortable(),

            Column::action('Aksi'),
        ];
    }

    public function actions(LeaveType $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('editLeaveType', ['id' => $row->id]),
        ];
    }
}
