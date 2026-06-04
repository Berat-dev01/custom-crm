<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', admin_trans('Admin Panel')) - {{ config('app.name') }}</title>

    <!-- Sidebar FOUC Prevention: set data-sidebar on <html> synchronously before CSS paints -->
    <script>
        (function() {
            var mode = localStorage.getItem('sidebarMode');
            var mobile = window.innerWidth < 1024;
            if (mobile) { mode = 'hidden'; } // Always start hidden on mobile
            else { mode = mode || 'expanded'; }
            document.documentElement.setAttribute('data-sidebar', mode);
        })();
    </script>

    <!-- Bootstrap 5 Grid & Utilities -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap-grid.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap-utilities.min.css" rel="stylesheet">

    @php
        $adminAssetPath = config('admin-panel.asset_path', 'vendor/admin-panel');
        $adminCssUrl = asset($adminAssetPath . '/css/admin.css') . '?v=' . (@filemtime(public_path($adminAssetPath . '/css/admin.css')) ?: time());
        $adminDropzoneCssUrl = asset($adminAssetPath . '/css/dropzone.css') . '?v=' . (@filemtime(public_path($adminAssetPath . '/css/dropzone.css')) ?: time());
        $adminJsUrl = asset($adminAssetPath . '/js/admin.js') . '?v=' . (@filemtime(public_path($adminAssetPath . '/js/admin.js')) ?: time());
    @endphp

    <!-- Admin Panel CSS -->
    <link rel="stylesheet" href="{{ $adminCssUrl }}">

    <!-- Dropzone.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
    <link rel="stylesheet" href="{{ $adminDropzoneCssUrl }}">

    @stack('styles')
</head>
<body class="no-transition">

    @if(app()->environment('demo'))
    <div style="position:fixed;top:0;left:0;right:0;z-index:9999;height:44px;background:#0f172a;border-bottom:1px solid #1e293b;display:flex;align-items:center;justify-content:space-between;padding:0 1rem;gap:0.75rem;">
        <div style="display:flex;align-items:center;gap:0.6rem;flex:1;min-width:0;overflow:hidden;">
            <span style="flex-shrink:0;background:#1e293b;color:#94a3b8;font-size:0.6rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;padding:0.2rem 0.45rem;border-radius:4px;border:1px solid #334155;">DEMO</span>
            <span style="color:#cbd5e1;font-size:0.775rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                🔒 Bu bir demo önizlemesidir — Tüm haklar <strong style="color:#f1f5f9;">Webakil</strong>'e aittir. Yazılım tamamen özelleştirilebilir ve geliştirilebilir. Bilgi için: <a href="mailto:info@webakil.com" style="color:#93c5fd;text-decoration:none;">info@webakil.com</a>
            </span>
        </div>
        <a href="https://webakil.com/iletisim"
           target="_blank"
           rel="noopener noreferrer"
           style="flex-shrink:0;background:#16a34a;color:#fff;text-decoration:none;font-size:0.75rem;font-weight:600;padding:0.3rem 0.9rem;border-radius:6px;border:1px solid #15803d;transition:background 0.15s;"
           onmouseover="this.style.background='#15803d'"
           onmouseout="this.style.background='#16a34a'">İletişime Geç</a>
    </div>
    <style>body { padding-top: 44px; }</style>
    @endif

    @php
        $adminPanelName = config('admin-panel.app_name', config('app.name', 'Admin Panel'));
        if (class_exists(\App\Models\Setting::class) && method_exists(\App\Models\Setting::class, 'get')) {
            $adminPanelName = \App\Models\Setting::get('store_name', $adminPanelName);
        }

        $adminUser = auth('admin')->user();
        $adminRoleLabel = 'Staff';
        $adminRoleVariant = 'secondary';

        if ($adminUser) {
            $roleNames = method_exists($adminUser, 'getRoleNames')
                ? $adminUser->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all()
                : [strtolower((string) ($adminUser->role ?? ''))];

            foreach (config('admin-panel.roles', []) as $role => [$label, $variant]) {
                if (in_array($role, $roleNames, true)) {
                    $adminRoleLabel = $label;
                    $adminRoleVariant = $variant;
                    break;
                }
            }
        }

        $adminCommandItems = $adminCommandItems ?? collect();
    @endphp

    <div class="admin-layout"
         x-data="{
             sidebarMode: (() => {
                 const stored = localStorage.getItem('sidebarMode');
                 const mobile = window.innerWidth < 1024;
                 if (mobile) return 'hidden'; // Always start hidden on mobile
                 return stored || 'expanded';
             })(),
             isMobile: window.innerWidth < 1024,
             get isExpanded() { return this.sidebarMode === 'expanded' },
             get isMini()     { return this.sidebarMode === 'mini' },
             get isHidden()   { return this.sidebarMode === 'hidden' },
             get showOverlay() { return this.sidebarMode === 'expanded' && this.isMobile },
             setSidebar(mode) {
                 this.sidebarMode = mode;
                 localStorage.setItem('sidebarMode', mode);
                 document.documentElement.setAttribute('data-sidebar', mode);
                 if (mode === 'mini') {
                     this.$dispatch('sidebar-minimized');
                 }
             },
             cycleSidebar() {
                 if (this.isMobile) {
                     this.setSidebar(this.sidebarMode === 'expanded' ? 'hidden' : 'expanded');
                 } else {
                     const modes = ['expanded', 'mini', 'hidden'];
                     this.setSidebar(modes[(modes.indexOf(this.sidebarMode) + 1) % 3]);
                 }
             },
             init() {
                 const onResize = () => {
                     const wasMobile = this.isMobile;
                     this.isMobile = window.innerWidth < 1024;
                     if (!wasMobile && this.isMobile && this.sidebarMode === 'mini') {
                         this.setSidebar('hidden');
                     }
                 };
                 window.addEventListener('resize', onResize);
                 this.$cleanup = () => window.removeEventListener('resize', onResize);
             }
         }"
         :class="'layout-sidebar-' + sidebarMode">

        <!-- Mobile Sidebar Overlay -->
        <div class="admin-sidebar-overlay"
             x-show="showOverlay"
             @click="setSidebar('hidden')"
             x-cloak></div>

        <!-- Sidebar -->
        <aside class="admin-sidebar"
               :class="{
                   'sidebar-expanded': sidebarMode === 'expanded',
                   'sidebar-mini':     sidebarMode === 'mini',
                   'sidebar-hidden':   sidebarMode === 'hidden'
               }">

            <!-- Logo Area -->
            <div class="admin-sidebar-logo">
                <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-logo-link">
                    @if(config('admin-panel.logo'))
                        <img src="{{ config('admin-panel.logo') }}" alt="{{ $adminPanelName }}">
                    @else
                        <span class="admin-logo-icon" aria-hidden="true">{{ strtoupper(substr($adminPanelName, 0, 1)) }}</span>
                        <h2 class="admin-logo-text">{{ $adminPanelName }}</h2>
                    @endif
                </a>
            </div>

            <!-- Navigation -->
            <nav class="admin-sidebar-nav">

                {{-- Main --}}
                <div class="sidebar-section-label">{{ admin_trans('Main') }}</div>
                <x-admin-panel::sidebar-item route="admin.dashboard" icon="layout-dashboard" label="Dashboard" />

                @stack('sidebar-nav')

                {{-- Sadece ecommerce / marketplace --}}
                @if(!in_array(config('kit.site_type'), ['blog', 'blank']))
                    <div class="sidebar-section-label">{{ admin_trans('Commerce') }}</div>
                    <x-admin-panel::sidebar-dropdown label="Catalog" icon="package">
                        <x-admin-panel::sidebar-item route="admin.products.index" label="Products" />
                        <x-admin-panel::sidebar-item route="admin.categories.index" label="Categories" />
                    </x-admin-panel::sidebar-dropdown>

                    <x-admin-panel::sidebar-dropdown label="Sales" icon="shopping-cart">
                        <x-admin-panel::sidebar-item route="admin.orders.index" label="Orders" />
                        <x-admin-panel::sidebar-item route="admin.coupons.index" label="Coupons" />
                    </x-admin-panel::sidebar-dropdown>
                @endif


                {{-- Customers --}}
                <div class="sidebar-section-label">{{ admin_trans('People') }}</div>
                <x-admin-panel::sidebar-dropdown label="Customers" icon="users">
                    <x-admin-panel::sidebar-item route="admin.users.index" label="Users" />
                    @if(!in_array(config('kit.site_type'), ['blog', 'blank']))
                        <x-admin-panel::sidebar-item route="admin.reviews.index" label="Reviews" />
                    @endif
                </x-admin-panel::sidebar-dropdown>

                {{-- Content --}}
                <div class="sidebar-section-label">{{ admin_trans('Content') }}</div>
                <x-admin-panel::sidebar-dropdown label="Content" icon="file-text">
                    @feature('cms.pages')
                        <x-admin-panel::sidebar-item route="admin.pages.index" label="Pages" />
                    @endfeature
                    @feature('cms.banners')
                        <x-admin-panel::sidebar-item route="admin.banners.index" label="Banners" />
                    @endfeature
                    @feature('blog.enabled')
                        <x-admin-panel::sidebar-item route="admin.posts.index" label="Blog Posts" />
                        <x-admin-panel::sidebar-item route="admin.tags.index" label="Tags" />
                    @endfeature
                </x-admin-panel::sidebar-dropdown>

                @feature('marketplace.enabled')
                    <div class="sidebar-section-label">{{ admin_trans('Marketplace') }}</div>
                    <x-admin-panel::sidebar-dropdown label="Marketplace" icon="store">
                        <x-admin-panel::sidebar-item route="admin.sellers.index" label="Sellers" />
                        <x-admin-panel::sidebar-item route="admin.withdrawals.index" label="Withdrawals" />
                    </x-admin-panel::sidebar-dropdown>
                @endfeature

                {{-- System --}}
                <div class="sidebar-section-label">{{ admin_trans('System') }}</div>
                <x-admin-panel::sidebar-dropdown label="System" icon="settings">
                    @if(auth('admin')->check() && auth('admin')->user()->role === 'superadmin')
                        <x-admin-panel::sidebar-item route="admin.staff.index" label="Staff" />
                    @endif
                    <x-admin-panel::sidebar-item route="admin.settings.index" label="Settings" />
                </x-admin-panel::sidebar-dropdown>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="admin-main">

            <!-- Topbar -->
            <header class="admin-topbar">

                <!-- Left: sidebar toggle + page title -->
                <div class="admin-topbar-left">
                    <button class="topbar-icon-btn topbar-sidebar-toggle"
                            @click="cycleSidebar()"
                            :title="isExpanded ? '{{ admin_trans('Collapse sidebar') }}' : (isMini ? '{{ admin_trans('Hide sidebar') }}' : '{{ admin_trans('Show sidebar') }}')">
                        <i data-lucide="panel-left-close" width="20" height="20"
                           x-show="isExpanded" style="display:none;"></i>
                        <i data-lucide="panel-left" width="20" height="20"
                           x-show="isMini" style="display:none;"></i>
                        <i data-lucide="menu" width="20" height="20"
                           x-show="isHidden"></i>
                    </button>
                    <h1 class="admin-topbar-title">@yield('page-title', admin_trans('Dashboard'))</h1>
                </div>

                <!-- Right: actions -->
                <div class="admin-topbar-actions">

                    @if($adminCommandItems->isNotEmpty())
                        <button
                            type="button"
                            class="topbar-command-trigger"
                            data-admin-command-trigger
                            title="{{ admin_trans('Search') }}"
                        >
                            <i data-lucide="search" width="16" height="16"></i>
                            <span>{{ admin_trans('Search') }}</span>
                            <kbd>⌘K</kbd>
                        </button>
                    @endif

                    @stack('topbar-actions')

                    {{-- Profile Dropdown --}}
                    <div class="topbar-profile" x-data="{ open: false }" @click.outside="open = false">

                        {{-- Trigger button --}}
                        <button class="topbar-profile-trigger"
                                @click="open = !open"
                                type="button"
                                :aria-expanded="open">
                            <div class="admin-user-avatar">
                                {{ strtoupper(substr(auth('admin')->user()->name ?? 'A', 0, 1)) }}
                            </div>
                            <div class="admin-user-info">
                                <span class="admin-user-name">{{ auth('admin')->user()->name ?? admin_trans('Admin') }}</span>
                                @if(auth('admin')->check())
                                    <span class="admin-user-role badge badge-{{ $adminRoleVariant }}">
                                        {{ admin_trans($adminRoleLabel) }}
                                    </span>
                                @endif
                            </div>
                            <i data-lucide="chevron-down" width="14" height="14"
                               class="topbar-profile-chevron"
                               :style="open ? 'transform:rotate(180deg)' : 'transform:rotate(0deg)'"></i>
                        </button>

                        {{-- Dropdown panel --}}
                        <div class="topbar-profile-dropdown"
                             x-show="open"
                             x-cloak>

                            {{-- Header row --}}
                            <div class="topbar-dropdown-header">
                                <div class="admin-user-avatar admin-user-avatar--lg">
                                    {{ strtoupper(substr(auth('admin')->user()->name ?? 'A', 0, 1)) }}
                                </div>
                                <div class="topbar-dropdown-header-info">
                                    <div class="topbar-dropdown-name">{{ auth('admin')->user()->name ?? admin_trans('Admin') }}</div>
                                    @if(auth('admin')->check())
                                        <span class="badge badge-{{ $adminRoleVariant }} badge-sm">
                                            {{ admin_trans($adminRoleLabel) }}
                                        </span>
                                    @endif
                                    <div class="topbar-dropdown-email">{{ auth('admin')->user()->email ?? '' }}</div>
                                </div>
                            </div>

                            <div class="topbar-dropdown-divider"></div>

                            {{-- Language selector --}}
                            @php
                                $adminLocale = $adminCurrentLocale ?? app()->getLocale();
                                $adminLanguageOptions = [
                                    'tr' => ['code' => 'TR', 'label' => 'Türkçe'],
                                    'en' => ['code' => 'EN', 'label' => 'English'],
                                ];
                            @endphp
                            <div class="topbar-language">
                                <div class="topbar-language-label">
                                    <i data-lucide="languages" width="15" height="15"></i>
                                    <span>{{ admin_trans('Language') }}</span>
                                </div>
                                <div class="topbar-language-options" role="group" aria-label="{{ admin_trans('Language') }}">
                                    @foreach($adminLanguageOptions as $locale => $language)
                                        <form method="POST" action="{{ route('admin.locale.update') }}">
                                            @csrf
                                            <input type="hidden" name="locale" value="{{ $locale }}">
                                            <button type="submit"
                                                    class="topbar-language-option {{ $adminLocale === $locale ? 'is-active' : '' }}"
                                                    @disabled($adminLocale === $locale)
                                                    title="{{ $language['label'] }}">
                                                <span class="topbar-language-code">{{ $language['code'] }}</span>
                                                <span class="topbar-language-name">{{ $language['label'] }}</span>
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>

                            <div class="topbar-dropdown-divider"></div>

                            {{-- Navigation items --}}
                            <div class="topbar-dropdown-items">
                                @if(Route::has('admin.profile.index'))
                                    <a href="{{ route('admin.profile.index') }}" class="topbar-dropdown-item">
                                        <i data-lucide="user" width="15" height="15"></i>
                                        <span>{{ admin_trans('My Profile') }}</span>
                                    </a>
                                @endif
                                <a href="{{ route('admin.settings.index') }}" class="topbar-dropdown-item">
                                    <i data-lucide="settings" width="15" height="15"></i>
                                    <span>{{ admin_trans('Account Settings') }}</span>
                                </a>
                            </div>

                            @if(Route::has('home'))
                                <div class="topbar-dropdown-divider"></div>
                                <div class="topbar-dropdown-items">
                                    <a href="{{ route('home') }}" class="topbar-dropdown-item"
                                       target="_blank" rel="noopener noreferrer">
                                        <i data-lucide="globe" width="15" height="15"></i>
                                        <span>{{ admin_trans('View Store') }}</span>
                                        <i data-lucide="external-link" width="12" height="12"
                                           class="topbar-dropdown-item-ext"></i>
                                    </a>
                                </div>
                            @endif

                            <div class="topbar-dropdown-divider"></div>

                            <div class="topbar-dropdown-items">
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="topbar-dropdown-item topbar-dropdown-item--danger">
                                        <i data-lucide="log-out" width="15" height="15"></i>
                                        <span>{{ admin_trans('Logout') }}</span>
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </header>

            @if($adminCommandItems->isNotEmpty())
                <div class="admin-command-palette" data-admin-command-palette hidden>
                    <div class="admin-command-backdrop" data-admin-command-close></div>
                    <section class="admin-command-dialog" role="dialog" aria-modal="true" aria-label="{{ admin_trans('Command Palette') }}">
                        <div class="admin-command-search">
                            <i data-lucide="search" width="18" height="18"></i>
                            <input
                                type="search"
                                placeholder="{{ admin_trans('Search...') }}"
                                autocomplete="off"
                                data-admin-command-input
                            >
                            <button type="button" class="admin-command-close" data-admin-command-close title="{{ admin_trans('Close') }}">
                                <i data-lucide="x" width="18" height="18"></i>
                            </button>
                        </div>

                        <div class="admin-command-results" data-admin-command-results>
                            @foreach($adminCommandItems as $commandItem)
                                <a
                                    href="{{ $commandItem['url'] }}"
                                    class="admin-command-item"
                                    data-admin-command-item
                                    data-label="{{ strtolower($commandItem['label'].' '.$commandItem['group']) }}"
                                >
                                    <span>
                                        <strong>{{ admin_trans($commandItem['label']) }}</strong>
                                        <small>{{ admin_trans($commandItem['group']) }}</small>
                                    </span>
                                    <i data-lucide="arrow-right" width="16" height="16"></i>
                                </a>
                            @endforeach
                        </div>

                        @stack('command-palette-footer')
                    </section>
                </div>
            @endif

            <!-- Breadcrumbs -->
            @hasSection('breadcrumbs')
            <div class="admin-breadcrumb-bar">
                @yield('breadcrumbs')
            </div>
            @endif

            <!-- Content -->
            <main class="admin-content">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- SortableJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <!-- Dropzone.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
    <script>
        // Prevent Dropzone from auto-discovering (must be set synchronously, before DOMContentLoaded)
        if (typeof Dropzone !== 'undefined') {
            Dropzone.autoDiscover = false;
        }
    </script>

    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    @include('admin-panel::partials.translations')
    <script src="{{ $adminJsUrl }}"></script>
    <script>
        // Initialize icons on page load
        lucide.createIcons();

        // After Alpine hydrates: remove no-transition class (double-rAF ensures initial paint is committed),
        // then re-initialize icons.
        document.addEventListener('alpine:initialized', () => {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    document.body.classList.remove('no-transition');
                });
            });
            setTimeout(() => lucide.createIcons(), 50);
        });
    </script>

    <!-- UI Helpers (SweetAlert2) -->
    @include('components.ui-helpers')

    @stack('scripts')
</body>
</html>
