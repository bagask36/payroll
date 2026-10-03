<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class AuditLogTable extends PowerGridComponent
{
    public string $tableName = 'audit_logs_table';

    public bool $showFilters = false;

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
        return AuditLog::query()
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->select('audit_logs.*', 'users.name as actor_name', 'users.email as actor_email');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('created_at')
            ->add('created_at_formatted', fn (AuditLog $log) => $log->created_at?->format('d/m/Y H:i:s') ?? '-')
            ->add('actor_name', fn (AuditLog $log) => e($log->actor_name ?? 'System'))
            ->add('actor_email', fn (AuditLog $log) => e($log->actor_email ?? '-'))
            ->add('action')
            ->add('action_badge', function (AuditLog $log) {
                $category = explode('.', $log->action)[0] ?? '';
                $colorClass = match ($category) {
                    'auth' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                    'attendance' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                    'leave' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800',
                    'payroll' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                    'document' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800',
                    default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700',
                };

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border '.$colorClass.'">'.e($log->action).'</span>';
            })
            ->add('auditable_display', function (AuditLog $log) {
                if (! $log->auditable_type) {
                    return '<span class="text-zinc-400">-</span>';
                }

                $shortName = class_basename($log->auditable_type);
                $id = e((string) ($log->auditable_id ?? '-'));

                return '<div class="text-xs font-mono"><div>'.e($shortName).'</div><div class="text-[11px] text-zinc-400">ID: '.$id.'</div></div>';
            })
            ->add('ip_address', fn (AuditLog $log) => e($log->ip_address ?? '-'));
    }

    public function columns(): array
    {
        return [
            Column::make('Waktu', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::make('Pelaku', 'actor_name', 'users.name')
                ->searchable()
                ->sortable(),

            Column::make('Aktivitas', 'action_badge', 'action')
                ->searchable()
                ->sortable(),

            Column::make('Entitas Target', 'auditable_display'),

            Column::make('IP Address', 'ip_address')
                ->searchable()
                ->sortable(),

            Column::action('Aksi'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('action')->placeholder('Filter aksi...'),
            Filter::inputText('ip_address')->placeholder('Filter IP...'),
        ];
    }

    public function actions(AuditLog $row): array
    {
        return [
            Button::add('viewPayload')
                ->slot('Payload')
                ->class('px-2.5 py-1 text-xs font-medium rounded-md bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 transition-colors cursor-pointer')
                ->dispatch('open-audit-payload', ['logId' => $row->id]),
        ];
    }
}
