@props([
    'label' => '',
    'value' => '',
    'icon' => null,
    'variant' => 'primary',  // primary, success, danger, warning, info
    'trend' => null,         // up, down, neutral
    'change' => null,        // e.g., '+12.5%'
])

@php
    $iconBgColor = match($variant) {
        'success' => 'var(--success-light)',
        'danger' => 'var(--danger-light)',
        'warning' => 'var(--warning-light)',
        'info' => 'var(--info-light)',
        default => 'var(--primary-light)',
    };

    $iconColor = match($variant) {
        'success' => 'var(--success)',
        'danger' => 'var(--danger)',
        'warning' => 'var(--warning)',
        'info' => 'var(--info)',
        default => 'var(--primary)',
    };

    $trendIcon = match($trend) {
        'up' => 'trending-up',
        'down' => 'trending-down',
        default => 'minus',
    };

    $trendColor = match($trend) {
        'up' => 'var(--success)',
        'down' => 'var(--danger)',
        default => 'var(--text-muted)',
    };

    $attributes = $attributes->class(['card']);
    $translatedLabel = is_string($label) && $label !== '' ? admin_trans($label) : $label;
@endphp

<div {{ $attributes }}>
    <div class="card-body">
        <div class="d-flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm text-secondary mb-1">{{ $translatedLabel }}</p>
                <h3 class="text-2xl font-semibold mb-0">{{ $value }}</h3>

                @if($trend && $change)
                    <div class="d-flex items-center gap-1 mt-2">
                        <i
                            data-lucide="{{ $trendIcon }}"
                            width="14"
                            height="14"
                            style="color: {{ $trendColor }};"
                        ></i>
                        <span class="text-xs" style="color: {{ $trendColor }};">
                            {{ $change }}
                        </span>
                    </div>
                @endif
            </div>

            @if($icon)
                <div
                    class="d-flex items-center justify-center rounded-lg"
                    style="width: 48px; height: 48px; background-color: {{ $iconBgColor }};"
                >
                    <i
                        data-lucide="{{ $icon }}"
                        width="24"
                        height="24"
                        style="color: {{ $iconColor }};"
                    ></i>
                </div>
            @endif
        </div>
    </div>
</div>
