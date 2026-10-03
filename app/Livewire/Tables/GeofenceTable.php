<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\GeofenceLocation;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class GeofenceTable extends PowerGridComponent
{
    public string $tableName = 'geofences_table';

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
        return GeofenceLocation::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', fn (GeofenceLocation $g) => '<span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($g->name).'</span>')
            ->add('coordinates', fn (GeofenceLocation $g) => '<span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">'.e((string) $g->latitude).', '.e((string) $g->longitude).'</span>')
            ->add('radius_display', fn (GeofenceLocation $g) => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">'.$g->radius_meters.' Meter</span>')
            ->add('status_badge', function (GeofenceLocation $g) {
                if ($g->is_active) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif Terverifikasi</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">Nonaktif</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Nama Area / Titik Presensi', 'name', 'geofence_locations.name')
                ->searchable()
                ->sortable(),

            Column::make('Koordinat GPS (Lat, Long)', 'coordinates'),

            Column::make('Radius Toleransi', 'radius_display', 'geofence_locations.radius_meters')
                ->sortable(),

            Column::make('Status', 'status_badge', 'geofence_locations.is_active')
                ->sortable(),

            Column::action('Aksi'),
        ];
    }

    public function actions(GeofenceLocation $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('editGeofence', ['id' => $row->id]),
        ];
    }
}
