<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('master.index') }}" class="text-xs text-zinc-500 hover:text-emerald-600 dark:text-zinc-400">Data Master</a>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Parameter PPh 21</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="calculator" class="size-7 text-emerald-600" />
                Parameter Pajak Penghasilan (PPh 21)
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Konfigurasi PTKP (Penghasilan Tidak Kena Pajak) dan tarif progresif Pasal 17 UU HPP untuk perhitungan payroll rumah sakit.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <flux:badge size="md" color="emerald" class="px-3 py-1 font-mono">
                PMK 168 / UU HPP Berlaku
            </flux:badge>
        </div>
    </div>

    {{-- Feedback Alert --}}
    @if ($feedbackMessage)
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <flux:icon name="check-circle" class="size-5 text-emerald-600 shrink-0" />
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-emerald-500 hover:text-emerald-700">
                <flux:icon name="x-mark" class="size-4" />
            </button>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800">
        <button 
            wire:click="$set('activeTab', 'ptkp')" 
            class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'ptkp' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Tabel PTKP Tahunan
        </button>
        <button 
            wire:click="$set('activeTab', 'tarif_pasal_17')" 
            class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'tarif_pasal_17' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Tarif Progresif Pasal 17 (UU HPP)
        </button>
    </div>

    {{-- Tab 1: PTKP Table --}}
    @if ($activeTab === 'ptkp')
        <div class="space-y-4">
            <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 text-blue-900 dark:text-blue-300 text-xs">
                <span class="font-semibold">Informasi PTKP:</span> Nilai PTKP (Penghasilan Tidak Kena Pajak) digunakan sebagai pengurang penghasilan neto tahunan pegawai untuk mendapatkan Penghasilan Kena Pajak (PKP).
            </div>

            <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
                <livewire:tables.pph21-ptkp-table />
            </flux:card>
        </div>
    @endif

    {{-- Tab 2: Tarif Pasal 17 --}}
    @if ($activeTab === 'tarif_pasal_17')
        <div class="space-y-4">
            <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 text-amber-900 dark:text-amber-300 text-xs">
                <span class="font-semibold">Tarif Pajak Progresif:</span> Berdasarkan UU No. 7 Tahun 2021 tentang Harmonisasi Peraturan Perpajakan (HPP). Tarif dikenakan secara berlapis terhadap Penghasilan Kena Pajak (PKP) tahunan.
            </div>

            <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
                <livewire:tables.pph21-tarif-table />
            </flux:card>
        </div>
    @endif

    {{-- Edit Modal --}}
    <flux:modal wire:model="showModal" class="w-full max-w-2xl space-y-4">
        <form wire:submit.prevent="save" class="space-y-4">
            <div>
                <flux:heading size="lg">Edit Parameter PPh 21</flux:heading>
                <flux:subheading>{{ $paramName }} ({{ $paramCode }})</flux:subheading>
            </div>

            <div class="space-y-3">
                @if ($paramCategory === 'ptkp')
                    <flux:input wire:model="paramAmount" label="Nilai PTKP Tahunan (Rp)" type="number" step="1000" required />
                @else
                    <flux:input wire:model="paramRate" label="Tarif Pajak (%)" type="number" step="0.1" min="0" max="100" required />
                    <flux:input wire:model="paramThresholdMin" label="Batas Minimal PKP (Rp)" type="number" step="1000" />
                    <flux:input wire:model="paramThresholdMax" label="Batas Maksimal PKP (Rp - kosong jika tak terhingga)" type="number" step="1000" />
                @endif

                <flux:input wire:model="effectiveFrom" label="Berlaku Mulai Tanggal" type="date" required />

                <div class="pt-2">
                    <flux:checkbox wire:model="paramIsActive" label="Parameter Aktif Digunakan" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button wire:click="$set('showModal', false)" variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary" class="bg-emerald-600 hover:bg-emerald-700 text-white">Simpan Perubahan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
