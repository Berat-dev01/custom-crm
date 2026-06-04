@props([
    'title' => null,
])

<header class="admin-topbar" {{ $attributes }}>
    <div class="admin-topbar-left">
        @isset($left)
            {{ $left }}
        @else
            <h1 class="admin-topbar-title">{{ $title ?? config('admin-panel.app_name', 'Admin Panel') }}</h1>
        @endisset
    </div>

    <div class="admin-topbar-actions">
        {{ $slot }}
    </div>
</header>
