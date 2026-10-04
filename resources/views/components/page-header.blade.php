@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'app-page-header']) }}>
    <div class="min-w-0">
        <h1 class="app-page-title">
            {{ $title }}
        </h1>
        @if($subtitle)
            <p class="app-page-subtitle">
                {{ $subtitle }}
            </p>
        @endif
        {{ $slot }}
    </div>

    @if(isset($actions))
        <div class="app-page-actions">
            {{ $actions }}
        </div>
    @endif
</div>
