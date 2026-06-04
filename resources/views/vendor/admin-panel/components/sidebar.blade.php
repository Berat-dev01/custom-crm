@props([
    'logo' => null,
    'appName' => null,
])

@php
    $appName = $appName ?? config('business.company_name', config('app.name', 'Laravel Kit'));
@endphp

<div x-data="{ mobileOpen: false }">
    <!-- Mobile Menu Toggle -->
    <button
        type="button"
        class="mobile-menu-toggle"
        @click="mobileOpen = !mobileOpen"
        aria-label="{{ admin_trans('Toggle Menu') }}"
    >
        <i data-lucide="menu" width="20" height="20"></i>
    </button>

    <!-- Sidebar Overlay (Mobile) -->
    <div
        class="sidebar-overlay"
        :class="{ 'active': mobileOpen }"
        @click="mobileOpen = false"
    ></div>

    <!-- Sidebar -->
    <aside
        class="admin-sidebar"
        :class="{ 'mobile-open': mobileOpen }"
        {{ $attributes }}
    >
        <!-- Logo -->
        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-logo">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $appName }}">
            @else
                <i data-lucide="box" width="32" height="32"></i>
            @endif
            <span>{{ $appName }}</span>
        </a>

        <!-- Navigation -->
        <nav class="admin-sidebar-nav">
            {{ $slot }}
        </nav>

        <!-- Footer (optional) -->
        @if(isset($footer))
            <div class="p-4 border-t" style="border-color: var(--sidebar-border);">
                {{ $footer }}
            </div>
        @endif
    </aside>
</div>
