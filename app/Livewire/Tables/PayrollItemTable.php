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

    protected ?bool $cachedIsThr = null;

    public function isThrRun(): bool
    {
        if ($this->cachedIsThr !== null) {
            return $this->cachedIsThr;
        }

        if (! $this->payrollRunId) {
            return $this->cachedIsThr = false;
        }

        return $this->cachedIsThr = (\App\Models\PayrollRun::where('id', $this->payrollRunId)->value('type') === 'thr');
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
            ->add('tenure_info', function (PayrollItem $row) {
                $meta = $row->metadata ?? [];
                $startDate = $meta['start_date'] ?? $row->employee?->currentEmployment?->start_date;
                $formattedStart = $startDate ? \Carbon\Carbon::parse($startDate)->format('d M Y') : '-';
                $tenureMonths = $meta['tenure_months'] ?? null;
                $tenureText = $tenureMonths !== null ? "{$tenureMonths} Bulan Masa Kerja" : '-';

                return "
                    <div class=\"text-xs\">
                        <div class=\"font-semibold text-zinc-800 dark:text-zinc-200\">{$tenureText}</div>
                        <div class=\"text-[11px] text-zinc-400 font-mono\">TMT: {$formattedStart}</div>
                    </div>
                ";
            })
            ->add('prorata_status', function (PayrollItem $row) {
                $meta = $row->metadata ?? [];
                $isProrated = $meta['is_prorated'] ?? false;
                $fraction = $meta['factor_fraction'] ?? '12/12';
                $percent = $meta['factor_percent'] ?? 100;

                if ($isProrated) {
                    return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800\">Prorata {$fraction} ({$percent}%)</span>";
                }

                return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800\">Penuh ({$percent}%)</span>";
            })
            ->add('base_wage_formatted', function (PayrollItem $row) {
                $meta = $row->metadata ?? [];
                $baseWage = $meta['base_wage'] ?? ((float) $row->basic_salary + (float) $row->allowances);

                return '<span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">Rp '.number_format((float) $baseWage, 0, ',', '.').'</span>';
            })
            ->add('gross_thr_formatted', function (PayrollItem $row) {
                $gross = (float) $row->basic_salary + (float) $row->allowances;

                return '<span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">Rp '.number_format($gross, 0, ',', '.').'</span>';
            })
            ->add('pph21_formatted', function (PayrollItem $row) {
                $tax = (float) $row->pph21_tax;
                if ($tax > 0) {
                    return '<span class="font-mono text-xs text-rose-600 dark:text-rose-400">-Rp '.number_format($tax, 0, ',', '.').'</span>';
                }

                return '<span class="font-mono text-xs text-zinc-400">Rp 0</span>';
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
                        <div class=\"text-[11px] font-mono text-zinc-400\">".e($account)."</div>
                    </div>
                ";
            });
    }

    public function columns(): array
    {
        if ($this->isThrRun()) {
            return [
                Column::make('Pegawai', 'employee_info', 'employees.full_name')
                    ->searchable()
                    ->sortable(),

                Column::make('Unit & Jabatan', 'unit_position'),

                Column::make('Masa Kerja & TMT', 'tenure_info'),

                Column::make('Status Prorata', 'prorata_status'),

                Column::make('Upah Acuan', 'base_wage_formatted'),

                Column::make('Bruto THR', 'gross_thr_formatted'),

                Column::make('PPh 21', 'pph21_formatted'),

                Column::make('Net THR', 'net_salary_formatted', 'payroll_items.net_salary')
                    ->sortable(),

                Column::make('Rekening Bank', 'bank_info'),

                Column::action('Opsi'),
            ];
        }

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
