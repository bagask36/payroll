@props([
    'items' => [],
    'level' => 0,
    'expandedFolders' => [],
    'folderItemsMap' => [],
    'loadingFolderId' => null,
])

@foreach ($items as $item)
    @php
        $isFolder = $item['is_folder'] ?? false;
        $itemId = $item['id'] ?? '';
        $isExpanded = $isFolder && !empty($expandedFolders[$itemId]);
        $mimeType = $item['mimeType'] ?? '';
        $isImage = str_starts_with($mimeType, 'image/');
        $isPdf = $mimeType === 'application/pdf';
        $children = $isFolder ? ($folderItemsMap[$itemId] ?? []) : [];
        $paddingLeft = ($level * 28) + 16;
    @endphp

    {{-- Item Row --}}
    <tr 
        class="transition-colors border-b border-zinc-100 dark:border-zinc-800/80 hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50 {{ $isFolder ? 'cursor-pointer select-none bg-zinc-50/30 dark:bg-zinc-800/20' : '' }}"
        @if($isFolder) wire:click="toggleFolder('{{ $itemId }}')" @endif
    >
        {{-- Name Column with Tree Indentation --}}
        <td class="py-2.5 pr-4 text-zinc-900 dark:text-zinc-100" style="padding-left: {{ $paddingLeft }}px;">
            <div class="flex items-center gap-2 min-w-0">
                @if($isFolder)
                    {{-- Preloader Spinner while this folder is loading --}}
                    <div wire:loading wire:target="toggleFolder('{{ $itemId }}')" class="size-4 flex items-center justify-center shrink-0">
                        <svg class="animate-spin size-3.5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    {{-- Regular Expand/Collapse Chevron when idle --}}
                    <div wire:loading.remove wire:target="toggleFolder('{{ $itemId }}')" class="shrink-0 flex items-center justify-center">
                        <button 
                            type="button" 
                            class="p-0.5 rounded text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-transform duration-200 cursor-pointer"
                            title="{{ $isExpanded ? 'Tutup folder' : 'Buka folder' }}"
                        >
                            @if($isExpanded)
                                <flux:icon name="chevron-down" class="size-4 text-emerald-600 dark:text-emerald-400" />
                            @else
                                <flux:icon name="chevron-right" class="size-4 text-zinc-400" />
                            @endif
                        </button>
                    </div>

                    {{-- Folder Icon --}}
                    <div class="size-7 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-200/60 dark:border-amber-800/60 shadow-2xs">
                        @if($isExpanded)
                            <flux:icon name="folder-open" class="size-4" />
                        @else
                            <flux:icon name="folder" class="size-4" />
                        @endif
                    </div>

                    <div class="min-w-0 flex items-center gap-2">
                        <span class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors truncate max-w-xs sm:max-w-md" title="{{ $item['name'] }}">
                            {{ $item['name'] }}
                        </span>

                        {{-- Preloader Pill while loading folder --}}
                        <span wire:loading wire:target="toggleFolder('{{ $itemId }}')" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800 animate-pulse">
                            <span class="size-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span>Memuat folder...</span>
                        </span>

                        @if($isExpanded)
                            <span wire:loading.remove wire:target="toggleFolder('{{ $itemId }}')" class="text-[10px] text-zinc-400 font-mono hidden sm:inline">
                                ({{ count($children) }} item)
                            </span>
                        @endif
                    </div>
                @else
                    {{-- File Indent Spacer --}}
                    <div class="size-4 shrink-0"></div>

                    {{-- File Icon --}}
                    <div class="size-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-200/60 dark:border-emerald-800/60 shadow-2xs">
                        @if($isImage)
                            <flux:icon name="photo" class="size-4" />
                        @elseif($isPdf)
                            <flux:icon name="document-text" class="size-4 text-rose-500" />
                        @else
                            <flux:icon name="document" class="size-4" />
                        @endif
                    </div>

                    <div class="min-w-0">
                        <span class="truncate block max-w-xs sm:max-w-md text-xs sm:text-sm font-medium text-zinc-800 dark:text-zinc-200" title="{{ $item['name'] }}">
                            {{ $item['name'] }}
                        </span>
                    </div>
                @endif
            </div>
        </td>

        {{-- Type Column --}}
        <td class="py-2.5 px-3 text-xs shrink-0">
            @if($isFolder)
                <flux:badge size="xs" color="amber" class="font-semibold text-[10px]">Folder</flux:badge>
            @else
                <flux:badge size="xs" color="zinc" class="font-mono text-[10px]">
                    {{ $isImage ? 'Gambar' : ($isPdf ? 'PDF' : 'File') }}
                </flux:badge>
            @endif
        </td>

        {{-- Size Column --}}
        <td class="py-2.5 px-3 text-zinc-600 dark:text-zinc-300 font-mono text-xs">
            @if($isFolder)
                <span class="text-zinc-400">—</span>
            @else
                {{ \App\Livewire\System\GoogleDriveStorageIndex::formatBytes((int) ($item['size'] ?? 0)) }}
            @endif
        </td>

        {{-- Modified Time Column --}}
        <td class="py-2.5 px-3 text-zinc-500 dark:text-zinc-400 text-xs">
            @php
                $time = $item['modifiedTime'] ?? $item['createdTime'] ?? null;
            @endphp
            @if(!empty($time))
                {{ \Carbon\Carbon::parse($time)->timezone('Asia/Jakarta')->format('d M Y, H:i') }}
            @else
                -
            @endif
        </td>

        {{-- Action Column --}}
        <td class="py-2.5 px-4 text-right">
            <div class="flex items-center justify-end gap-1.5" @if($isFolder) onclick="event.stopPropagation()" @endif>
                @if($isFolder)
                    <flux:button 
                        wire:click="toggleFolder('{{ $itemId }}')" 
                        wire:target="toggleFolder('{{ $itemId }}')"
                        size="xs" 
                        variant="subtle"
                        class="cursor-pointer text-[11px]"
                    >
                        <span wire:loading.remove wire:target="toggleFolder('{{ $itemId }}')">
                            {{ $isExpanded ? 'Tutup' : 'Buka' }}
                        </span>
                        <span wire:loading wire:target="toggleFolder('{{ $itemId }}')" class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                            <svg class="animate-spin size-3 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memuat...</span>
                        </span>
                    </flux:button>
                @endif

                @if(!empty($item['webViewLink']))
                    <flux:button 
                        href="{{ $item['webViewLink'] }}" 
                        target="_blank" 
                        icon="arrow-top-right-on-square" 
                        size="xs" 
                        variant="subtle"
                        class="cursor-pointer"
                        title="Buka di Google Drive"
                    >
                        Drive
                    </flux:button>
                @endif
            </div>
        </td>
    </tr>

    {{-- Inline Preloader Row under the folder while fetching from Google Drive --}}
    @if($isFolder && !$isExpanded)
        <tr wire:loading wire:target="toggleFolder('{{ $itemId }}')" class="border-b border-zinc-100 dark:border-zinc-800/80 bg-emerald-50/20 dark:bg-emerald-950/20 transition-all">
            <td colspan="5" class="py-2.5 pr-4 text-xs text-emerald-700 dark:text-emerald-300 font-medium" style="padding-left: {{ (($level + 1) * 28) + 16 }}px;">
                <div class="flex items-center gap-2.5">
                    <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Mengambil berkas & subdirektori dari Google Drive...</span>
                </div>
            </td>
        </tr>
    @endif

    {{-- Render Subfolder Children if Expanded --}}
    @if($isFolder && $isExpanded)
        @if(count($children) > 0)
            @include('livewire.system.partials.directory-tree-node', [
                'items' => $children,
                'level' => $level + 1,
                'expandedFolders' => $expandedFolders,
                'folderItemsMap' => $folderItemsMap,
                'loadingFolderId' => $loadingFolderId,
            ])
        @else
            {{-- Empty Folder Placeholder --}}
            <tr class="border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/40 dark:bg-zinc-800/20">
                <td colspan="5" class="py-2.5 pr-4 text-xs text-zinc-400 italic" style="padding-left: {{ (($level + 1) * 28) + 16 }}px;">
                    <div class="flex items-center gap-2">
                        <flux:icon name="folder-minus" class="size-3.5 text-zinc-400" />
                        <span>(Folder ini kosong)</span>
                    </div>
                </td>
            </tr>
        @endif
    @endif
@endforeach
