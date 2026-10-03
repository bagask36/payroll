<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Laporan Eksekutif</span>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Cuti & Izin</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="calendar-days" class="size-7 text-emerald-600" />
                Laporan Penggunaan Cuti & Izin Pegawai
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Statistik pemanfaatan hak cuti tahunan, cuti sakit, melahirkan, serta riwayat persetujuan berjenjang.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button wire:click="exportCsv" variant="primary" icon="arrow-down-tray" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                Ekspor CSV
            </flux:button>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Cuti Disetujui</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $totalApproved }}</div>
            <div class="text-xs text-emerald-600 font-medium">Permohonan disahkan</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Menunggu Approval</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $totalPending }}</div>
            <div class="text-xs text-amber-600 font-medium">Perlu review supervisor/HR</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total Hari Digunakan</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $totalDaysTaken }} Hari</div>
            <div class="text-xs text-zinc-500 font-medium">Akumulasi hari cuti sah</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <flux:input wire:model.live="startDate" label="Mulai Tanggal" type="date" />
        <flux:input wire:model.live="endDate" label="Sampai Tanggal" type="date" />

        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status Pengajuan</label>
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="all">Semua Status</flux:select.option>
                <flux:select.option value="approved">Disetujui (Approved)</flux:select.option>
                <flux:select.option value="submitted">Diajukan (Submitted)</flux:select.option>
                <flux:select.option value="rejected">Ditolak (Rejected)</flux:select.option>
            </flux:select>
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Jenis Cuti</label>
            <flux:select wire:model.live="leaveTypeFilter">
                <flux:select.option value="">Semua Jenis Cuti</flux:select.option>
                @foreach($leaveTypes as $lt)
                    <flux:select.option value="{{ $lt->id }}">{{ $lt->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">No. Pengajuan & Tgl</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">Pegawai & Unit</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">Jenis Cuti</th>
                    <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 uppercase">Periode & Durasi</th>
                    <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($leaveRequests as $req)
                    <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="text-xs font-mono font-bold text-emerald-700 dark:text-emerald-400">{{ $req->request_number }}</div>
                            <div class="text-2xs text-zinc-500">{{ $req->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $req->employee->name ?? '-' }}</div>
                            <div class="text-xs text-zinc-500">{{ $req->employee->employment?->unit?->name ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-zinc-800 dark:text-zinc-200">
                            {{ $req->leaveType->name ?? '-' }}
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap text-xs">
                            <div class="font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $req->start_date->format('d M') }} - {{ $req->end_date->format('d M Y') }}
                            </div>
                            <div class="text-2xs font-semibold text-emerald-600 dark:text-emerald-400">{{ $req->total_days }} Hari Kerja</div>
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            <flux:badge size="sm" color="{{ $req->status === 'approved' ? 'emerald' : ($req->status === 'rejected' ? 'rose' : 'amber') }}">
                                {{ $req->status }}
                            </flux:badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-zinc-500">
                            Tidak ditemukan data permohonan cuti pada rentang tanggal tersebut.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $leaveRequests->links() }}
    </div>
</div>
