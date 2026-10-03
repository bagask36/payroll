@props([
    'colspan' => 1,
    'icon' => 'inbox',
    'title' => 'Tidak ada data',
    'description' => null,
])

<flux:table.row>
    <flux:table.cell :colspan="$colspan" class="py-16!">
        <div class="flex flex-col items-center justify-center gap-2 text-center whitespace-normal">
            <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-white/10">
                <flux:icon :name="$icon" class="size-6 text-zinc-400" />
            </div>
            <flux:heading size="sm">{{ $title }}</flux:heading>
            @if ($description)
                <flux:text class="max-w-sm">{{ $description }}</flux:text>
            @endif
            {{ $slot }}
        </div>
    </flux:table.cell>
</flux:table.row>
