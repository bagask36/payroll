<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class DoctorTable extends PowerGridComponent
{
    public string $tableName = 'doctors_table';

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
        return Doctor::query()
            ->leftJoin('employees', 'doctors.employee_id', '=', 'employees.id')
            ->select([
                'doctors.*',
                'employees.full_name as employee_name',
            ])
            ->with(['employee']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('doctor_display', function (Doctor $doc) {
                $cleanName = str_replace(['dr. ', 'drg. ', 'Sp.'], '', $doc->name);
                $initials = strtoupper(substr(trim($cleanName), 0, 2));
                $url = route('doctors.show', $doc->id);

                return '
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 font-semibold text-xs shrink-0">
                            '.e($initials).'
                        </div>
                        <div>
                            <a href="'.e($url).'" class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                '.e($doc->name).'
                            </a>
                            <div class="text-xs text-zinc-500">'.e($doc->specialization).'</div>
                        </div>
                    </div>
                ';
            })
            ->add('name')
            ->add('specialization')
            ->add('doctor_type', function (Doctor $doc) {
                if ($doc->employee_id) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Dokter Organik</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">Dokter Mitra</span>';
            })
            ->add('str_badge', function (Doctor $doc) {
                $strNo = e($doc->str_number ?? '-');
                $valid = $doc->str_valid_until;
                if (! $valid) {
                    return "<div class=\"font-mono text-xs text-zinc-700 dark:text-zinc-300\">{$strNo}</div>";
                }

                $dateStr = $valid->format('d/m/Y');
                if ($doc->isStrExpired()) {
                    $badge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800\">Kedaluwarsa ({$dateStr})</span>";
                } elseif ($valid->isBetween(now(), now()->addDays(60))) {
                    $days = $valid->diffInDays(now());
                    $badge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800\">{$days} Hari Lagi</span>";
                } else {
                    $badge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700\">s/d {$dateStr}</span>";
                }

                return "
                    <div class=\"font-mono text-xs text-zinc-700 dark:text-zinc-300\">{$strNo}</div>
                    <div class=\"mt-1\">{$badge}</div>
                ";
            })
            ->add('sip_badge', function (Doctor $doc) {
                $sipNo = e($doc->sip_number ?? '-');
                $valid = $doc->sip_valid_until;
                if (! $valid) {
                    return "<div class=\"font-mono text-xs text-zinc-700 dark:text-zinc-300\">{$sipNo}</div>";
                }

                $dateStr = $valid->format('d/m/Y');
                if ($doc->isSipExpired()) {
                    $badge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800\">Kedaluwarsa ({$dateStr})</span>";
                } elseif ($valid->isBetween(now(), now()->addDays(60))) {
                    $days = $valid->diffInDays(now());
                    $badge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800\">{$days} Hari Lagi</span>";
                } else {
                    $badge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700\">s/d {$dateStr}</span>";
                }

                return "
                    <div class=\"font-mono text-xs text-zinc-700 dark:text-zinc-300\">{$sipNo}</div>
                    <div class=\"mt-1\">{$badge}</div>
                ";
            })
            ->add('phone_number', function (Doctor $doc) {
                $phone = e($doc->phone_number ?? '-');
                $email = $doc->email !== '-' ? e($doc->email) : null;
                if ($email) {
                    return '<div><div class="font-medium text-zinc-800 dark:text-zinc-200">'.$phone.'</div><div class="text-xs text-zinc-400">'.$email.'</div></div>';
                }

                return $phone;
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Dokter & Spesialisasi', 'doctor_display', 'doctors.name')
                ->searchable()
                ->sortable(),

            Column::make('Kategori', 'doctor_type')
                ->sortable(),

            Column::make('Lisensi STR (Kemenkes)', 'str_badge', 'doctors.str_valid_until')
                ->sortable(),

            Column::make('Izin Praktik SIP (Dinkes)', 'sip_badge', 'doctors.sip_valid_until')
                ->sortable(),

            Column::make('Kontak', 'phone_number', 'doctors.phone_number')
                ->searchable(),

            Column::action('Opsi'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('doctors.name')->placeholder('Cari nama dokter...'),
            Filter::inputText('doctors.specialization')->placeholder('Cari spesialisasi...'),
        ];
    }

    public function actions(Doctor $row): array
    {
        return [
            Button::add('viewProfile')
                ->slot('Lihat Profil')
                ->route('doctors.show', ['id' => $row->id])
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer'),
        ];
    }
}
