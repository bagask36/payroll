@props([
    'message' => null,
    'type' => 'success',
    'dismissible' => true,
    'wireDismiss' => "\$set('feedbackMessage', null)",
])

@if($message)
    @php
        $typeClasses = match($type) {
            'danger', 'error' => 'app-alert-danger',
            'warning' => 'app-alert-warning',
            'info' => 'app-alert-info',
            default => 'app-alert-success',
        };

        $iconName = match($type) {
            'danger', 'error' => 'exclamation-circle',
            'warning' => 'exclamation-triangle',
            'info' => 'information-circle',
            default => 'check-circle',
        };
    @endphp

    <div 
        x-data="{ 
            msg: @js($message), 
            variant: @js($type),
            showToast() {
                if (window.swalToast) {
                    window.swalToast(this.msg, this.variant);
                } else {
                    document.addEventListener('DOMContentLoaded', () => {
                        window.swalToast?.(this.msg, this.variant);
                    }, { once: true });
                }
            }
        }" 
        x-init="showToast()"
        {{ $attributes->merge(['class' => 'app-alert ' . $typeClasses]) }}
    >
        <div class="flex items-center gap-3 min-w-0">
            <flux:icon :name="$iconName" class="size-5 shrink-0" />
            <span class="truncate">{{ $message }}</span>
        </div>

        @if($dismissible)
            <button 
                type="button" 
                @if($wireDismiss) wire:click="{{ $wireDismiss }}" @endif
                x-on:click="$el.closest('.app-alert').remove()" 
                class="shrink-0 p-1 rounded-lg opacity-70 hover:opacity-100 transition-opacity cursor-pointer"
                title="Tutup"
            >
                <flux:icon name="x-mark" class="size-4" />
            </button>
        @endif
    </div>
@endif

