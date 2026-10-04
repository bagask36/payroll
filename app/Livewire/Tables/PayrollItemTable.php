<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\PayrollItem;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PayrollItemTable extends PowerGridComponent
{
    public string $tableName = 'payroll_items_table';

    public ?int $payrollRunId = null;

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
        return PayrollItem::query()
            ->leftJoin('employees', 'payroll_items.employee_id', '=', 'employees.id')
            ->leftJoin('units', 'employees.unit_id', '=', 'units.id')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->select([
                'payroll_items.*',
                'employees.full_name as employee_name',
                'employees.nip as employee_nip',
                'units.name as unit_name',
                'positions.title as position_title',
            ])
            ->with(['employee.unit', 'employee.position'])
            ->when($this->payrollRunId, fn ($q) => $q->where('payroll_items.payroll_run_id', $this->payrollRunId));
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('employee_info', function (PayrollItem $row) {
                $name = e($row->employee?->full_name ?? $row->employee_name ?? '-');
                $nip = e($row->employee?->nip ?? $row->employee_nip ?? '-');

                return "
                    <div class=\"font-semibold text-zinc-900 dark:text-zinc-100\">{$name}</div>
                    <div class=\"text-xs font-mono text-zinc-400\">{$nip}</div>
                ";
            })
            ->add('employee_name')
            ->add('employee_nip')
            ->add('unit_position', function (PayrollItem $row) {
                $unit = e($row->employee?->unit?->name ?? $row->unit_name ?? '-');
                $pos = e($row->employee?->position?->title ?? $row->position_title ?? '-');

                return "
                    <div class=\"text-xs text-zinc-500\">
                        <div>{$unit}</div>
                        <div class=\"text-[11px] text-zinc-400\">{$pos}</div>
                    </div>
                ";
            })
            ->add('basic_salary_formatted', fn (PayrollItem $row) => '<span class="font-mono text-xs text-zinc-700 dark:text-zinc-300">Rp '.number_format((float) $row->basic_salary, 0, ',', '.').'</span>')
            ->add('bpjs_kes_formatted', fn (PayrollItem $row) => '<span class="font-mono text-xs text-rose-600 dark:text-rose-400">-Rp '.number_format((float) $row->bpjs_kes_employee, 0, ',', '.').'</span>')
            ->add('bpjs_tk_formatted', fn (PayrollItem $row) => '<span class="font-mono text-xs text-rose-600 dark:text-rose-400">-Rp '.number_format((float) $row->bpjs_tk_employee, 0, ',', '.').'</span>')
            ->add('net_salary_formatted', fn (PayrollItem $row) => '<span class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400">Rp '.number_format((float) $row->net_salary, 0, ',', '.').'</span>')
            ->add('bank_info', function (PayrollItem $row) {
                $bank = e($row->bank_name ?? '-');
                $account = $row->bank_account_number ? '**** '.substr($row->bank_account_number, -4) : '-';

                return "
                    <div class=\"text-xs text-zinc-500\">
                        <div>{$bank}</div>
                        <div class=\"text-[11px] font-mono text-zinc-400\">".e($account).'</div>
                    </div>
                ';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Pegawai', 'employee_info', 'employees.full_name')
                ->searchable()
                ->sortable(),

            Column::make('Unit & Jabatan', 'unit_position'),

            Column::make('Gaji Pokok', 'basic_salary_formatted', 'payroll_items.basic_salary')
                ->sortable(),

            Column::make('BPJS Kes (1%)', 'bpjs_kes_formatted', 'payroll_items.bpjs_kes_employee')
                ->sortable(),

            Column::make('BPJS TK (3%)', 'bpjs_tk_formatted', 'payroll_items.bpjs_tk_employee')
                ->sortable(),

            Column::make('Gaji Bersih', 'net_salary_formatted', 'payroll_items.net_salary')
                ->sortable(),

            Column::make('Rekening Bank', 'bank_info'),

            Column::action('Opsi'),
        ];
    }

    public function actions(PayrollItem $row): array
    {
        return [
            Button::add('viewDetail')
                ->slot('Rincian Slip')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('view-item-detail', ['itemId' => $row->id]),
        ];
    }
}
