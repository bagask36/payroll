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

            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Kode Status</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Deskripsi Status Pernikahan</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nilai PTKP Tahunan</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">PTKP Bulanan (Acuan)</th>
                            <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($ptkpList as $item)
                            <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap text-xs font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                    {{ $item->code }}
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $item->name }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                    Rp {{ number_format((float) $item->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-xs font-mono text-zinc-500">
                                    Rp {{ number_format((float) $item->amount / 12, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <flux:badge size="sm" color="{{ $item->is_active ? 'emerald' : 'zinc' }}">
                                        {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </flux:badge>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                                    <flux:button wire:click="edit({{ $item->id }})" size="xs" variant="subtle" icon="pencil-square">
                                        Edit
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Tab 2: Tarif Pasal 17 --}}
    @if ($activeTab === 'tarif_pasal_17')
        <div class="space-y-4">
            <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 text-amber-900 dark:text-amber-300 text-xs">
                <span class="font-semibold">Tarif Pajak Progresif:</span> Berdasarkan UU No. 7 Tahun 2021 tentang Harmonisasi Peraturan Perpajakan (HPP). Tarif dikenakan secara berlapis terhadap Penghasilan Kena Pajak (PKP) tahunan.
            </div>

            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Lapisan</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Rentang Penghasilan Kena Pajak (PKP)</th>
                            <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tarif Pajak</th>
                            <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($tarifPasal17List as $lap)
                            <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap text-xs font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                    {{ $lap->code }}
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $lap->name }}
                                    <div class="text-xs font-mono text-zinc-500 mt-0.5">
                                        Rp {{ number_format((float) $lap->threshold_min, 0, ',', '.') }} 
                                        s/d 
                                        {{ $lap->threshold_max ? 'Rp ' . number_format((float) $lap->threshold_max, 0, ',', '.') : 'Tak Terhingga' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <flux:badge size="md" color="emerald" class="font-bold font-mono">
                                        {{ number_format((float) $lap->rate * 100, 1) }}%
                                    </flux:badge>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <flux:badge size="sm" color="{{ $lap->is_active ? 'emerald' : 'zinc' }}">
                                        {{ $lap->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </flux:badge>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                                    <flux:button wire:click="edit({{ $lap->id }})" size="xs" variant="subtle" icon="pencil-square">
                                        Edit
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Edit Modal --}}
    <flux:modal wire:model="showModal" class="md:w-md">
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
