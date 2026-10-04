<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Doctor;
use App\Models\Document;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class DocumentTable extends PowerGridComponent
{
    public string $tableName = 'documents_table';

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
        $user = Auth::user();
        $query = Document::query()->with(['documentable', 'uploadedBy']);

        if ($user && ! $user->hasPermission('document.view_all')) {
            $query->where(function ($q) use ($user) {
                $q->where('uploaded_by', $user->id);

                if ($user->employee) {
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->where('documentable_type', Employee::class)
                            ->where('documentable_id', $user->employee->id);
                    });
                }

                if ($user->doctor) {
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->where('documentable_type', Doctor::class)
                            ->where('documentable_id', $user->doctor->id);
                    });
                }
            });
        }

        return $query;
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('document_display', function (Document $doc) {
                $name = e($doc->original_name);
                $uuid = e(substr($doc->uuid, 0, 13));

                return "
                    <div class=\"font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2\">
                        <span class=\"truncate max-w-xs\">{$name}</span>
                    </div>
                    <div class=\"text-[11px] font-mono text-zinc-400 mt-0.5\">UUID: {$uuid}...</div>
                ";
            })
            ->add('original_name')
            ->add('type_badge', function (Document $doc) {
                $badgeColor = match ($doc->document_type) {
                    'str', 'sip' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800',
                    'contract' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800',
                    'ktp', 'kk' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
                    'ijazah', 'certificate' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
                    default => 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700',
                };

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border '.e($badgeColor).'">'.e(strtoupper($doc->document_type)).'</span>';
            })
            ->add('owner_info', function (Document $doc) {
                if ($doc->documentable instanceof Employee) {
                    return '
                        <div class="font-medium text-zinc-800 dark:text-zinc-200">'.e($doc->documentable->full_name).'</div>
                        <div class="text-[11px] font-mono text-zinc-400">Pegawai ('.e($doc->documentable->nip).')</div>
                    ';
                }
                if ($doc->documentable instanceof Doctor) {
                    return '
                        <div class="font-medium text-emerald-700 dark:text-emerald-400">'.e($doc->documentable->name).'</div>
                        <div class="text-[11px] text-zinc-400">Dokter Spesialis</div>
                    ';
                }

                return '<span class="text-zinc-400">-</span>';
            })
            ->add('file_info', function (Document $doc) {
                $ext = strtoupper(substr($doc->mime_type ?? 'bin', -3));
                $kb = number_format($doc->file_size / 1024, 1);

                return "
                    <div class=\"text-xs font-mono text-zinc-500\">
                        <div>{$ext}</div>
                        <div class=\"text-[11px] text-zinc-400\">{$kb} KB</div>
                    </div>
                ";
            })
            ->add('created_at_formatted', fn (Document $doc) => '<div class="text-xs text-zinc-500 font-mono">'.e($doc->created_at->format('d/m/Y H:i')).'</div>');
    }

    public function columns(): array
    {
        return [
            Column::make('Nama Dokumen & Berkas', 'document_display', 'documents.original_name')
                ->searchable()
                ->sortable(),

            Column::make('Jenis Dokumen', 'type_badge', 'documents.document_type')
                ->sortable(),

            Column::make('Pemilik (Personil)', 'owner_info'),

            Column::make('Format & Ukuran', 'file_info', 'documents.file_size')
                ->sortable(),

            Column::make('Tanggal Unggah', 'created_at_formatted', 'documents.created_at')
                ->sortable(),

            Column::action('Opsi'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('document_type')
                ->dataSource([
                    ['name' => 'Semua Jenis Dokumen', 'id' => ''],
                    ['name' => 'KTP', 'id' => 'ktp'],
                    ['name' => 'KK', 'id' => 'kk'],
                    ['name' => 'Ijazah', 'id' => 'ijazah'],
                    ['name' => 'Kontrak Kerja', 'id' => 'contract'],
                    ['name' => 'STR', 'id' => 'str'],
                    ['name' => 'SIP', 'id' => 'sip'],
                    ['name' => 'Sertifikat', 'id' => 'certificate'],
                    ['name' => 'Lainnya', 'id' => 'other'],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(Document $row): array
    {
        $actions = [];

        if (Auth::user()?->can('download', $row)) {
            $actions[] = Button::add('downloadDoc')
                ->slot('Unduh')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('download-document', ['documentId' => $row->id]);
        }

        return $actions;
    }
}
