@props([
    'searchPlaceholder' => 'Cari...',
    'activeFilters' => 0,
    'perPageOptions' => [10, 25, 50, 100],
])

{{--
    Datatable toolbar: search, filter slot, per-page selector and reset.
    Expects the parent Livewire component to use App\Livewire\Concerns\WithDataTable.
--}}
<div {{ $attributes->class('flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between') }}>
    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <div class="w-full sm:w-72">
            <flux:input
                wire:model.live.debounce.300ms="search"
                size="sm"
                icon="magnifying-glass"
                :placeholder="$searchPlaceholder"
                clearable
                aria-label="Cari data"
            />
        </div>

        @isset($filters)
            {{ $filters }}
        @endisset

        @if ($activeFilters > 0)
            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">
                Reset filter
                <flux:badge size="sm" color="zinc" class="ms-1">{{ $activeFilters }}</flux:badge>
            </flux:button>
        @endif
    </div>

    <div class="flex items-center gap-2">
        @isset($actions)
            {{ $actions }}
        @endisset

        <flux:select wire:model.live="perPage" size="sm" class="w-fit!" aria-label="Jumlah baris per halaman">
            @foreach ($perPageOptions as $option)
                <flux:select.option value="{{ $option }}">{{ $option }} / halaman</flux:select.option>
            @endforeach
        </flux:select>
    </div>
</div>
