<x-layouts.app title="SIM SDM RS Juwita">
    <div class="flex min-h-screen flex-col items-center justify-center p-6 lg:p-8">
        <div class="w-full max-w-xl text-center space-y-6">
            <div class="inline-flex items-center gap-2">
                <flux:badge color="emerald" size="sm" inset="top bottom">Laravel 13</flux:badge>
                <flux:badge color="zinc" size="sm" inset="top bottom">Livewire + Flux</flux:badge>
                <flux:badge color="blue" size="sm" inset="top bottom">PostgreSQL</flux:badge>
            </div>

            <flux:heading size="2xl" class="font-bold tracking-tight">SIM SDM RS Juwita</flux:heading>
            <flux:subheading size="lg" class="text-zinc-600 dark:text-zinc-400">
                A modern payroll & HR management application built with Laravel, Livewire, Flux UI, and PostgreSQL.
            </flux:subheading>

            <div class="flex items-center justify-center gap-3 pt-2">
                <flux:button variant="primary" icon="sparkles" href="https://fluxui.dev" target="_blank">
                    Flux UI Docs
                </flux:button>
                <flux:button variant="subtle" icon="book-open" href="https://laravel.com/docs" target="_blank">
                    Laravel Docs
                </flux:button>
            </div>
        </div>
    </div>
</x-layouts.app>
