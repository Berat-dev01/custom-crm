@props([
    'href' => '#',
    'icon' => null,
    'divider' => false,      // If true, renders as divider
])

@if($divider)
    <div class="dropdown-divider"></div>
@else
    @php
        $attributes = $attributes->class(['dropdown-item']);
    @endphp

    <a href="{{ $href }}" {{ $attributes }}>
        @if($icon)
            <i data-lucide="{{ $icon }}" width="16" height="16"></i>
        @endif
        {{ $slot }}
    </a>
@endif
