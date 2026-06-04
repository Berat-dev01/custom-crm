@props([
    'variant' => 'primary',  // primary, secondary, success, danger, warning, outline, ghost
    'size' => 'md',          // sm, md, lg
    'icon' => null,          // Lucide icon name
    'iconPosition' => 'left', // left, right
    'loading' => false,
    'type' => 'button',      // button, submit, reset
    'href' => null,          // If provided, renders as <a> tag
    'disabled' => false,
])

@php
    $baseClass = 'btn';
    $variantClass = 'btn-' . $variant;
    $sizeClass = $size !== 'md' ? 'btn-' . $size : '';
    $loadingClass = $loading ? 'btn-loading' : '';

    // If icon only (no slot content)
    $iconOnlyClass = (!$slot->isEmpty() || $loading) ? '' : 'btn-icon';

    $classes = trim("{$baseClass} {$variantClass} {$sizeClass} {$loadingClass} {$iconOnlyClass}");

    $attributes = $attributes->class([$classes])->merge([
        'type' => $type,
        'disabled' => $disabled || $loading,
    ]);

    // Icon size based on button size
    $iconSize = match($size) {
        'sm' => '14',
        'lg' => '18',
        default => '16',
    };

    $slotHtml = trim($slot->toHtml());
    $slotIsPlainText = $slotHtml !== '' && $slotHtml === strip_tags($slotHtml);
    $slotText = $slotIsPlainText ? admin_trans($slotHtml) : null;
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->except(['type', 'disabled']) }}>
        @if($icon && $iconPosition === 'left' && !$loading)
            <i data-lucide="{{ $icon }}" width="{{ $iconSize }}" height="{{ $iconSize }}"></i>
        @endif

        @if(!$slot->isEmpty())
            @if($slotIsPlainText)
                {{ $slotText }}
            @else
                {{ $slot }}
            @endif
        @endif

        @if($icon && $iconPosition === 'right' && !$loading)
            <i data-lucide="{{ $icon }}" width="{{ $iconSize }}" height="{{ $iconSize }}"></i>
        @endif
    </a>
@else
    <button {{ $attributes }}>
        @if($icon && $iconPosition === 'left' && !$loading)
            <i data-lucide="{{ $icon }}" width="{{ $iconSize }}" height="{{ $iconSize }}"></i>
        @endif

        @if(!$slot->isEmpty())
            @if($slotIsPlainText)
                {{ $slotText }}
            @else
                {{ $slot }}
            @endif
        @endif

        @if($icon && $iconPosition === 'right' && !$loading)
            <i data-lucide="{{ $icon }}" width="{{ $iconSize }}" height="{{ $iconSize }}"></i>
        @endif
    </button>
@endif
