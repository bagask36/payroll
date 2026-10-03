@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'color' => 'zinc',
])

@php
    $iconClasses = match ($color) {
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
        'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
        'red' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
        'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400',
        default => 'bg-zinc-100 text-zinc-600 dark:bg-white/10 dark:text-zinc-300',
    };
@endphp

<flux:card {{ $attributes->class('flex items-center gap-4 p-4!') }}>
    @if ($icon)
        <div class="flex size-11 shrink-0 items-center justify-center rounded-lg {{ $iconClasses }}">
            <flux:icon :name="$icon" class="size-5" />
        </div>
    @endif
    <div class="min-w-0">
        <flux:text class="truncate text-xs font-medium uppercase tracking-wide">{{ $label }}</flux:text>
        <div class="mt-0.5 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $value }}</div>
        @if ($hint)
            <flux:text class="truncate text-xs">{{ $hint }}</flux:text>
        @endif
    </div>
</flux:card>
