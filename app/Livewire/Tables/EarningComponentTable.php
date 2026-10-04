<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\SalaryComponent;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class EarningComponentTable extends PowerGridComponent
{
    public string $tableName = 'earning_components_table';

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
        return SalaryComponent::query()->whereIn('type', ['allowance', 'earning']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code', fn (SalaryComponent $c) => '<span class="font-mono font-semibold text-xs text-emerald-700 dark:text-emerald-400">'.e($c->code).'</span>')
            ->add('name', function (SalaryComponent $c) {
                $desc = $c->description ? '<div class="text-xs text-zinc-400 mt-0.5">'.e($c->description).'</div>' : '';

                return '<div><span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($c->name).'</span>'.$desc.'</div>';
            })
            ->add('nature_badge', function (SalaryComponent $c) {
                $fixedBadge = $c->is_fixed
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">Rutin (Tetap)</span>'
                    : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Variabel (Kehadiran)</span>';

                $taxBadge = $c->is_taxable
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Kena PPh 21</span>'
                    : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-600 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700">Non-Pajak</span>';

                return '<div class="flex items-center gap-1.5 flex-wrap">'.$fixedBadge.$taxBadge.'</div>';
            })
            ->add('amount_display', function (SalaryComponent $c) {
                if ($c->default_amount && $c->default_amount > 0) {
                    $suffix = $c->is_fixed ? '/bln' : '/hari';

                    return '<span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">Rp '.number_format((float) $c->default_amount, 0, ',', '.').' <span class="text-[10px] text-zinc-400 font-normal">'.$suffix.'</span></span>';
                }

                return '<span class="text-zinc-400 text-xs italic">Kustom / Rumus</span>';
            })
            ->add('status_badge', function (SalaryComponent $c) {
                if ($c->is_active) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">Nonaktif</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Kode', 'code', 'salary_components.code')
                ->searchable()
                ->sortable(),

            Column::make('Komponen Penghasilan', 'name', 'salary_components.name')
                ->searchable()
                ->sortable(),

            Column::make('Sifat & Perlakuan Pajak', 'nature_badge'),

            Column::make('Nominal Standar', 'amount_display', 'salary_components.default_amount')
                ->sortable(),

            Column::make('Status', 'status_badge', 'salary_components.is_active')
                ->sortable(),

            Column::action('Opsi'),
        ];
    }

    public function actions(SalaryComponent $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('editComponent', ['id' => $row->id]),
        ];
    }
}
