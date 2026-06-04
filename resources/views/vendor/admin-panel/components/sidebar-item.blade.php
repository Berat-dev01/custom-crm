@props([
    'route' => null,
    'href' => null,
    'icon' => null,
    'label' => '',
    'badge' => null,         // Badge text
    'badgeVariant' => 'primary',
    'active' => false,
])

@php
    $url = $href ?? ($route ? route($route) : '#');
    $translatedLabel = $label ? admin_trans($label) : '';

    // Check if this item is active
    $isActive = $active;
    if ($route && !$active) {
        $isActive = request()->routeIs($route) || request()->routeIs($route . '.*');
    }

    $itemClasses = 'sidebar-item';
    if ($isActive) {
        $itemClasses .= ' active';
    }

    $attributes = $attributes->class([$itemClasses]);
@endphp

<a href="{{ $url }}" title="{{ $translatedLabel }}" {{ $attributes }}>
    @if($icon)
        <i data-lucide="{{ $icon }}" width="18" height="18"></i>
    @endif

    <span class="flex-1">{{ $translatedLabel }}</span>

    @if($badge)
        <span class="badge badge-{{ $badgeVariant }} badge-sm">{{ $badge }}</span>
    @endif
</a>
