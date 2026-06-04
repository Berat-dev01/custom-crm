@props([
    'label' => '',
    'icon' => null,
    'open' => false,
    'storageKey' => null,
])

@php
    $dropdownId = $attributes->get('id', 'dropdown-' . uniqid());
    $storageKey = $storageKey ?? 'sidebar-dropdown-' . str_replace(' ', '-', strtolower($label));
    $openInitial = $open ? 'true' : 'false';
    $translatedLabel = $label ? admin_trans($label) : '';
@endphp

<div
    class="sidebar-dropdown"
    x-data="{
        open: {{ $openInitial }},
        activeOpen: {{ $openInitial }},
        init() {
            const stored = localStorage.getItem('{{ $storageKey }}');
            if (this.activeOpen) {
                this.open = true;
                return;
            }
            if (stored !== null) {
                this.open = stored === 'true';
            }
        },
        toggle() {
            this.open = !this.open;
            localStorage.setItem('{{ $storageKey }}', this.open);
        },
        close() {
            this.open = false;
            localStorage.setItem('{{ $storageKey }}', 'false');
        }
    }"
    @sidebar-minimized.window="close()"
    {{ $attributes }}
>
    <!-- Dropdown Toggle -->
    <button
        type="button"
        class="sidebar-dropdown-toggle"
        :class="{ 'open': open, 'active': activeOpen }"
        @click="toggle()"
        title="{{ $translatedLabel }}"
    >
        <div class="d-flex items-center gap-3">
            @if($icon)
                <i data-lucide="{{ $icon }}" width="18" height="18"></i>
            @endif
            <span>{{ $translatedLabel }}</span>
        </div>
        <i data-lucide="chevron-down" width="16" height="16"></i>
    </button>

    <!-- Dropdown Content -->
    <div
        class="sidebar-dropdown-content"
        x-show="open"
        x-collapse
        x-cloak
    >
        {{ $slot }}
    </div>
</div>
