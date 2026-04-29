@extends('admin-panel::layouts.app')

{{--
    CRM Layout — extends the generic admin-panel layout and injects CRM-specific
    content into the named stack slots it provides:

      @stack('sidebar-nav')           ← CRM navigation groups
      @stack('topbar-actions')        ← notification bell
      @stack('command-palette-footer')← full-text search shortcut

    All CRM views should extend this layout instead of admin-panel::layouts.app.
    That way nothing CRM-specific ever lives inside the shared package.
--}}

@push('styles')
    @vite('resources/css/crm.css')
@endpush

@push('sidebar-nav')
    @if(!empty($crmNavigationGroups ?? []))
        <div class="sidebar-section-label">{{ admin_trans('CRM') }}</div>
        @foreach(($crmNavigationGroups ?? []) as $group)
            @php
                $visibleItems = collect($group['items'])
                    ->filter(fn ($item) => \Illuminate\Support\Facades\Gate::allows($item['permission']))
                    ->values();
            @endphp

            @if($visibleItems->isNotEmpty())
                <x-admin-panel::sidebar-dropdown
                    :label="$group['label']"
                    :icon="$group['icon']"
                    :open="$group['active']"
                    storage-key="sidebar-dropdown-crm-{{ str($group['label'])->slug() }}"
                >
                    @foreach($visibleItems as $item)
                        <x-admin-panel::sidebar-item
                            :route="$item['route']"
                            :label="$item['label']"
                            :active="$item['active']"
                        />
                    @endforeach
                </x-admin-panel::sidebar-dropdown>
            @endif
        @endforeach
    @endif
@endpush

@push('topbar-actions')
    @if(Route::has('crm.notifications.index'))
        <div
            class="topbar-notif"
            x-data="{ open: false }"
            @click.outside="open = false"
            data-admin-notifications
            data-endpoint="{{ route('crm.notifications.index') }}"
            data-view-all-url="{{ route('crm.notifications.page') }}"
            data-read-all-url="{{ route('crm.notifications.read-all') }}"
            data-read-url-pattern="{{ route('crm.notifications.read', ['notification' => '__NOTIFICATION__']) }}"
        >
            <button class="topbar-icon-btn"
                    type="button"
                    @click="open = !open"
                    title="{{ admin_trans('Notifications') }}"
                    :aria-expanded="open"
                    data-admin-notifications-toggle>
                <i data-lucide="bell" width="18" height="18"></i>
                <span class="topbar-badge" data-admin-notifications-badge hidden>0</span>
            </button>
            <div class="topbar-notif-dropdown" x-show="open" x-cloak>
                <div class="topbar-dropdown-header" style="padding: var(--space-3) var(--space-4);">
                    <span class="topbar-notif-heading">{{ admin_trans('Notifications') }}</span>
                    <span class="topbar-notif-summary" data-admin-notifications-summary>{{ admin_trans('All caught up') }}</span>
                    <button type="button" class="topbar-notif-mark-all" data-admin-notifications-read-all hidden>{{ admin_trans('Mark all as read') }}</button>
                </div>
                <div class="topbar-dropdown-divider"></div>
                <div class="topbar-notif-loading" data-admin-notifications-loading hidden>
                    <i data-lucide="loader-circle" width="18" height="18"></i>
                    <span>{{ admin_trans('Loading') }}</span>
                </div>
                <div class="topbar-notif-error" data-admin-notifications-error hidden>
                    <i data-lucide="alert-triangle" width="18" height="18"></i>
                    <span>{{ admin_trans('Notifications could not be loaded') }}</span>
                </div>
                <div class="topbar-notif-list" data-admin-notifications-list hidden></div>
                <div class="topbar-notif-empty" data-admin-notifications-empty>
                    <i data-lucide="bell-off" width="28" height="28"></i>
                    <p>{{ admin_trans('No notifications') }}</p>
                </div>
                <div class="topbar-dropdown-divider"></div>
                <div class="topbar-notif-footer">
                    <a href="{{ route('crm.notifications.page') }}" class="topbar-notif-view-all" data-admin-notifications-view-all>
                        {{ admin_trans('View all notifications') }}
                    </a>
                </div>
            </div>
        </div>
    @endif
@endpush

@push('command-palette-footer')
    @if(Route::has('crm.search'))
        <form method="GET" action="{{ route('crm.search') }}" class="admin-command-footer">
            <input type="hidden" name="q" data-admin-command-query>
            <button type="submit" class="admin-command-submit">
                <i data-lucide="search" width="16" height="16"></i>
                <span>{{ admin_trans('Search CRM records') }}</span>
            </button>
        </form>
    @endif
@endpush
