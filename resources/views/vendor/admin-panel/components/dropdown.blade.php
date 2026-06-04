@props([
    'align' => 'left',       // left, right
    'width' => '200px',
])

@php
    $alignmentClass = $align === 'right' ? 'dropdown-menu-right' : '';
@endphp

<div class="dropdown" x-data="{ open: false }" @click.away="open = false" {{ $attributes }}>
    <!-- Trigger -->
    <div @click="open = !open" class="cursor-pointer">
        @if(isset($trigger))
            {{ $trigger }}
        @else
            <button type="button" class="btn btn-secondary">
                <span>{{ admin_trans('Dropdown') }}</span>
                <i data-lucide="chevron-down" width="16" height="16"></i>
            </button>
        @endif
    </div>

    <!-- Menu -->
    <div
        x-show="open"
        class="dropdown-menu {{ $alignmentClass }}"
        style="min-width: {{ $width }}; display: none;"
    >
        {{ $slot }}
    </div>
</div>
