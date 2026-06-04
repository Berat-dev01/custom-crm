@props([
    'variant' => 'secondary', // primary, success, danger, warning, info, secondary
    'size' => 'md',          // sm, md, lg
    'dot' => false,          // Show dot indicator
    'pill' => false,         // Pill style (fully rounded)
])

@php
    $baseClass = 'badge';
    $variantClass = 'badge-' . $variant;
    $sizeClass = $size !== 'md' ? 'badge-' . $size : '';

    $classes = trim("{$baseClass} {$variantClass} {$sizeClass}");

    $attributes = $attributes->class([$classes]);

    if ($pill) {
        $attributes = $attributes->merge(['style' => 'border-radius: 999px;']);
    }

    $slotHtml = trim($slot->toHtml());
    $slotIsPlainText = $slotHtml !== '' && $slotHtml === strip_tags($slotHtml);
    $slotText = $slotIsPlainText ? admin_trans($slotHtml) : null;
@endphp

<span {{ $attributes }}>
    @if($dot)
        <span class="d-inline-block" style="width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; margin-right: 4px;"></span>
    @endif
    @if($slotIsPlainText)
        {{ $slotText }}
    @else
        {{ $slot }}
    @endif
</span>
