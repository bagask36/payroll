@props([
    'icon' => 'folder-open',
    'title' => 'Belum ada data',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-8 text-center rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/40']) }}>
    <div class="size-12 rounded-xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 dark:text-zinc-500 mb-3 shadow-2xs">
        <flux:icon :name="$icon" class="size-6" />
    </div>
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h3>
    @if($description)
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-sm">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-4 flex items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
