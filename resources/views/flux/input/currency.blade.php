@blaze(fold: true, unsafe: [
    'name', 'label', 'badge',
    'description', 'description:trailing',
    'label:badge', 'label:aside', 'label:trailing',
    'error:name', 'error:bag', 'error:message', 'error:icon', 'error:nested', 'error:deep',
])

@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'variant' => 'outline',
    'size' => null,
    'placeholder' => 'Rp. 0',
    'prefix' => 'Rp. ',
    'clearable' => false,
    'disabled' => false,
    'required' => false,
    'invalid' => null,
])

@php
$wireModel = $attributes->wire('model');
$wireModelName = $wireModel?->value();
$wireModelLive = $wireModel?->hasModifier('live');

$classes = Flux::classes()
    ->add('w-full border block disabled:shadow-none dark:shadow-none')
    ->add('appearance-none font-mono')
    ->add(match ($size) {
        default => 'text-base sm:text-sm rounded-lg py-2 h-10 leading-[1.375rem]',
        'sm' => 'text-sm rounded-md py-1.5 h-8 leading-[1.125rem]',
        'xs' => 'text-xs rounded-md py-1.5 h-6 leading-[1.125rem]',
    })
    ->add('ps-3.5 pe-3')
    ->add(match ($variant) {
        'outline' => 'bg-white dark:bg-white/10 dark:disabled:bg-white/[7%]',
        'filled'  => 'bg-zinc-800/5 dark:bg-white/10 dark:disabled:bg-white/[7%]',
    })
    ->add(match ($variant) {
        'outline' => 'text-zinc-800 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 disabled:text-zinc-500 dark:disabled:text-zinc-400',
        'filled'  => 'text-zinc-800 dark:text-zinc-100 placeholder-zinc-500 dark:placeholder-white/60 disabled:placeholder-zinc-400',
    })
    ->add(match ($variant) {
        'outline' => 'shadow-2xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5',
        'filled'  => 'border-0',
    })
    ->add(match ($variant) {
        'outline' => 'data-invalid:shadow-none data-invalid:border-red-500 dark:data-invalid:border-red-500 disabled:data-invalid:border-red-500 dark:disabled:data-invalid:border-red-500',
        'filled' => 'data-invalid:border-red-500',
    })
    ->add('[&:not(:disabled)]:focus:border-emerald-600 dark:[&:not(:disabled)]:focus:border-emerald-500 [&:not(:disabled)]:focus:ring-2 [&:not(:disabled)]:focus:ring-emerald-500/20')
    ->add($attributes->pluck('class:input'))
    ;
@endphp

<flux:with-field :$attributes :$name>
    <div 
        x-data="currencyInput({
            @if ($wireModelName)
                value: $wire.entangle('{{ $wireModelName }}'){{ $wireModelLive ? '.live' : '' }},
            @else
                value: null,
            @endif
            prefix: @js($prefix)
        })"
        {{ $attributes->only('class')->class('w-full relative block group/input') }} 
        data-flux-input
    >
        <input
            type="text"
            inputmode="numeric"
            autocomplete="off"
            x-ref="input"
            x-model="display"
            x-on:input="onInput($event)"
            x-on:keydown="onKeyDown($event)"
            x-on:focus="onFocus($event)"
            placeholder="{{ $placeholder }}"
            @if ($required) required @endif
            @if ($disabled) disabled @endif
            {{ $attributes->whereDoesntStartWith('wire:model')->except(['class', 'placeholder', 'prefix', 'clearable', 'disabled', 'required', 'variant', 'size', 'name', 'type', 'step', 'min', 'max'])->class($classes) }}
            @if (isset($name)) name="{{ $name }}" @endif
            @unblaze(scope: ['name' => $name ?? null, 'invalid' => $invalid ?? false])
            <?php if ($scope['invalid'] || ($scope['name'] && $errors->has($scope['name']))): ?>
                aria-invalid="true" data-invalid
            <?php endif; ?>
            @endunblaze
            data-flux-control
            data-flux-group-target
        />

        @if ($clearable)
            <div 
                x-show="display" 
                x-cloak 
                class="absolute top-0 bottom-0 flex items-center pe-2 end-0 text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer"
                x-on:click="clearInput()"
            >
                <flux:icon name="x-mark" variant="mini" class="size-4" />
            </div>
        @endif
    </div>
</flux:with-field>
