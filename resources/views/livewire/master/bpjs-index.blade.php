<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Data: Regulasi Tarif BPJS &amp; Iuran Wajib
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Tarif persentase iuran BPJS Kesehatan dan BPJS Ketenagakerjaan (JKK, JKM, JHT, JP) serta batas plafon upah.
            </flux:subheading>
        </div>
    </div>

    @if($feedbackMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center gap-3">
            <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
            <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
        </div>
    @endif

    {{-- Legal Notice Banner --}}
    <div class="p-4 rounded-xl bg-blue-50/75 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900 flex items-start gap-3">
        <flux:icon name="information-circle" class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
        <div class="text-xs text-blue-900 dark:text-blue-200 space-y-1">
            <p class="font-semibold">Kepatuhan Regulasi Ketenagakerjaan &amp; Penggajian Indonesia</p>
            <p class="text-blue-700 dark:text-blue-300">
                Tarif BPJS mengacu pada PP No. 44/2015, PP No. 45/2015, dan Perpres No. 64/2020. Setiap perubahan tarif atau plafon akan otomatis berdampak pada kalkulasi slip gaji berikutnya tanpa mengubah riwayat batch yang telah difinalisasi.
            </p>
        </div>
    </div>

    {{-- Data Table --}}
    <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Program Jaminan Sosial</flux:table.column>
                <flux:table.column align="end">Tarif Pegawai</flux:table.column>
                <flux:table.column align="end">Tarif RS (Pemberi Kerja)</flux:table.column>
                <flux:table.column align="end">Plafon Maksimal Upah</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($bpjsRules as $rule)
                    <flux:table.row :key="$rule->id">
                        <flux:table.cell class="font-semibold text-zinc-900 dark:text-zinc-100 uppercase">
                            BPJS {{ $rule->program_name }}
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono text-xs font-semibold text-rose-600 dark:text-rose-400">
                            {{ number_format($rule->employee_rate * 100, 2) }}%
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400">
                            {{ number_format($rule->employer_rate * 100, 2) }}%
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                            @if($rule->wage_ceiling)
                                Rp {{ number_format((float) $rule->wage_ceiling, 0, ',', '.') }}
                            @else
                                <span class="text-zinc-400">Tanpa Batas</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="xs" :color="$rule->is_active ? 'emerald' : 'zinc'">
                                {{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editBpjs({{ $rule->id }})">
                                Ubah Tarif
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- MODAL BPJS RULE --}}
    <flux:modal wire:model="showBpjsModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">Edit Tarif Regulasi BPJS</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">{{ strtoupper($bpjsProgramName) }}</flux:subheading>
        </div>

        <form wire:submit="saveBpjs" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="bpjsEmployeeRate" type="number" step="0.0001" label="Tarif Pegawai (Desimal)" placeholder="Contoh: 0.01" required />
                <flux:input wire:model="bpjsEmployerRate" type="number" step="0.0001" label="Tarif RS (Desimal)" placeholder="Contoh: 0.04" required />
            </div>
            <flux:input wire:model="bpjsWageCeiling" type="number" step="1000" label="Plafon Maksimal Upah (Kosongkan jika tanpa batas)" placeholder="Misal: 12000000" />
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" wire:model="bpjsIsActive" id="bpjsActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                <label for="bpjsActive" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Status Aturan Aktif</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Tarif</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
