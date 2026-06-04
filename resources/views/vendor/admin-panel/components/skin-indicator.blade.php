{{--
    Admin Skin Indicator Component
    Shows current active skin and metadata
    Usage: <x-admin.skin-indicator />
--}}

<div class="card">
    <div class="card-header">
        <h3 class="text-lg text-bold mb-0">{{ admin_trans('Active Skin') }}</h3>
    </div>
    <div class="card-body">
        <div class="mb-md">
            <strong>{{ admin_trans('Current Skin:') }}</strong>
            <span class="badge {{ config('theme.skin') === 'default' ? 'badge-primary' : 'badge-warning' }} ml-sm">
                {{ config('theme.skin') }}
            </span>
        </div>

        @if(config('theme.skin') !== 'default')
            <div class="alert alert-warning mb-md">
                <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i>
                <div>
                    <strong>{{ admin_trans('Custom skin active') }}</strong>
                    <p class="text-sm mb-0">{{ admin_trans('Layout switching is disabled when using a custom skin. The skin controls all layout and styling decisions.') }}</p>
                </div>
            </div>

            {{-- Load skin metadata if available --}}
            @php
                $skinPath = resource_path('views/skins/' . config('theme.skin'));
                $skinJsonPath = $skinPath . '/skin.json';
                $skinMeta = null;

                if (file_exists($skinJsonPath)) {
                    $skinMeta = json_decode(file_get_contents($skinJsonPath), true);
                }
            @endphp

            @if($skinMeta)
                <div class="mb-md">
                    <h4 class="text-md text-bold mb-sm">{{ admin_trans('Skin Details') }}</h4>
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <dl>
                                <dt class="text-sm text-bold text-muted">{{ admin_trans('Name') }}</dt>
                                <dd class="mb-sm">{{ $skinMeta['name'] ?? admin_trans('N/A') }}</dd>

                                <dt class="text-sm text-bold text-muted">{{ admin_trans('Version') }}</dt>
                                <dd class="mb-sm">{{ $skinMeta['version'] ?? admin_trans('N/A') }}</dd>

                                <dt class="text-sm text-bold text-muted">{{ admin_trans('Author') }}</dt>
                                <dd class="mb-sm">{{ $skinMeta['author'] ?? admin_trans('N/A') }}</dd>
                            </dl>
                        </div>
                        <div class="col-12 col-md-6">
                            <dt class="text-sm text-bold text-muted">{{ admin_trans('Description') }}</dt>
                            <dd class="mb-sm">{{ $skinMeta['description'] ?? admin_trans('No description provided') }}</dd>
                        </div>
                    </div>
                </div>

                @if(!empty($skinMeta['overrides']))
                    <div class="mb-md">
                        <h4 class="text-sm text-bold mb-xs">{{ admin_trans('View Overrides (:count)', ['count' => count($skinMeta['overrides'])]) }}</h4>
                        <ul class="text-sm mb-0">
                            @foreach($skinMeta['overrides'] as $override)
                                <li>{{ $override }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($skinMeta['css_overrides']))
                    <div class="mb-md">
                        <h4 class="text-sm text-bold mb-xs">{{ admin_trans('CSS Overrides (:count)', ['count' => count($skinMeta['css_overrides'])]) }}</h4>
                        <ul class="text-sm mb-0">
                            @foreach($skinMeta['css_overrides'] as $override)
                                <li>{{ $override }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($skinMeta['notes']))
                    <div>
                        <h4 class="text-sm text-bold mb-xs">{{ admin_trans('Notes') }}</h4>
                        <ul class="text-sm mb-0">
                            @foreach($skinMeta['notes'] as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @else
                <div class="alert alert-info">
                    <i data-lucide="info" style="width: 16px; height: 16px;"></i>
                    <div>
                        <p class="text-sm mb-0">
                            {{ admin_trans('No skin.json metadata file found. Create one at:') }}
                            <code class="d-block mt-xs">{{ $skinPath }}/skin.json</code>
                        </p>
                    </div>
                </div>
            @endif

            {{-- Skin file count --}}
            @php
                $skinFiles = [];
                if (is_dir($skinPath)) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($skinPath, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );

                    foreach ($iterator as $file) {
                        if ($file->isFile() && $file->getExtension() === 'php') {
                            $skinFiles[] = str_replace($skinPath . '/', '', $file->getPathname());
                        }
                    }
                }
            @endphp

            @if(count($skinFiles) > 0)
                <div class="alert alert-info">
                    <i data-lucide="file-code" style="width: 16px; height: 16px;"></i>
                    <div>
                        <strong>{{ admin_trans(':count view files', ['count' => count($skinFiles)]) }}</strong> {{ admin_trans('in this skin') }}
                        <details class="mt-sm">
                            <summary class="text-sm" style="cursor: pointer;">{{ admin_trans('Show files') }}</summary>
                            <ul class="text-xs mt-xs mb-0">
                                @foreach($skinFiles as $file)
                                    <li><code>{{ $file }}</code></li>
                                @endforeach
                            </ul>
                        </details>
                    </div>
                </div>
            @endif

            {{-- Skin CSS --}}
            @php
                $skinCssPath = public_path('css/skins/' . config('theme.skin') . '/skin.css');
                $skinCssExists = file_exists($skinCssPath);
            @endphp

            @if($skinCssExists)
                <div class="d-flex align-center gap-sm mb-md">
                    <i data-lucide="check-circle" style="width: 16px; height: 16px; color: var(--color-success);"></i>
                    <span class="text-sm">{{ admin_trans('Custom CSS file found:') }} <code>public/css/skins/{{ config('theme.skin') }}/skin.css</code></span>
                </div>
            @else
                <div class="d-flex align-center gap-sm mb-md">
                    <i data-lucide="alert-circle" style="width: 16px; height: 16px; color: var(--color-warning);"></i>
                    <span class="text-sm">{{ admin_trans('No custom CSS file found (optional)') }}</span>
                </div>
            @endif
        @else
            <div class="alert alert-success">
                <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                <div>
                    <strong>{{ admin_trans('Default skin active') }}</strong>
                    <p class="text-sm mb-0">{{ admin_trans('Using kit default views and layouts. Layout switching is enabled via admin settings.') }}</p>
                </div>
            </div>

            <div class="mt-md">
                <h4 class="text-sm text-bold mb-sm">{{ admin_trans('Available Skins') }}</h4>
                @php
                    $skinsPath = resource_path('views/skins');
                    $availableSkins = [];

                    if (is_dir($skinsPath)) {
                        $skins = scandir($skinsPath);
                        foreach ($skins as $skin) {
                            if ($skin !== '.' && $skin !== '..' && is_dir($skinsPath . '/' . $skin)) {
                                $availableSkins[] = $skin;
                            }
                        }
                    }
                @endphp

                @if(count($availableSkins) > 0)
                    <div class="d-flex flex-wrap gap-xs">
                        @foreach($availableSkins as $skin)
                            <span class="badge badge-outline">{{ $skin }}</span>
                        @endforeach
                    </div>
                    <p class="text-xs text-muted mt-sm mb-0">
                        {{ admin_trans('To activate a skin, set') }} <code>THEME_SKIN={{ $availableSkins[0] }}</code> {{ admin_trans('in your .env file or update the database setting.') }}
                    </p>
                @else
                    <p class="text-sm text-muted mb-0">{{ admin_trans('No custom skins found in') }} <code>resources/views/skins/</code></p>
                @endif
            </div>
        @endif

        {{-- Documentation link --}}
        <div class="border-top pt-md mt-md">
            <a href="{{ asset('SKINS.md') }}" target="_blank" class="btn btn-outline btn-sm">
                <i data-lucide="book-open" style="width: 14px; height: 14px;"></i>
                {{ admin_trans('View Skin Documentation') }}
            </a>
        </div>
    </div>
</div>

<style>
    dl {
        margin: 0;
    }

    dt {
        font-weight: var(--font-weight-medium);
    }

    dd {
        margin-left: 0;
    }

    code {
        background: var(--color-gray-100);
        padding: 2px 6px;
        border-radius: var(--radius-sm);
        font-size: 0.9em;
        font-family: monospace;
    }

    details summary:hover {
        color: var(--color-primary);
    }
</style>
