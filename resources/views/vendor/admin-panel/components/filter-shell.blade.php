@props([
    'action',
    'method' => 'GET',
    'resetUrl' => null,
    'activeCount' => 0,
    'advancedOpen' => false,
    'autoSubmit' => true,
])

@php
    $hasAdvanced = isset($advanced) && trim($advanced->toHtml()) !== '';
    $hasSaved = isset($saved) && trim($saved->toHtml()) !== '';
@endphp

<x-admin-panel::card class="admin-filter-shell-card">
    <form
        method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}"
        action="{{ $action }}"
        class="admin-filter-shell"
        data-admin-ajax-filter-form
        data-admin-auto-submit="{{ $autoSubmit ? '1' : '0' }}"
    >
        @if(strtoupper($method) !== 'GET')
            @csrf
            @if(!in_array(strtoupper($method), ['POST'], true))
                @method($method)
            @endif
        @endif

        <div class="admin-filter-shell-top">
            <div class="admin-filter-shell-compact">
                {{ $compact ?? $slot }}
            </div>

            <div class="admin-filter-shell-toolbar">
                @if($activeCount > 0)
                    <span class="badge badge-info badge-sm">{{ $activeCount }} active</span>
                @endif

                @if($hasAdvanced)
                    <button
                        type="button"
                        class="btn btn-ghost btn-sm"
                        data-admin-filter-toggle
                        aria-expanded="{{ $advancedOpen ? 'true' : 'false' }}"
                    >
                        <i data-lucide="sliders-horizontal" width="14" height="14"></i>
                        <span>{{ admin_trans('Advanced Filters') }}</span>
                    </button>
                @endif

                @if($hasSaved)
                    <button
                        type="button"
                        class="btn btn-ghost btn-sm"
                        data-admin-filter-saved-toggle
                        aria-expanded="false"
                    >
                        <i data-lucide="bookmark" width="14" height="14"></i>
                        <span>{{ admin_trans('Saved') }}</span>
                    </button>
                @endif

                <x-admin-panel::button type="submit" size="sm" icon="search">
                    {{ admin_trans('Apply') }}
                </x-admin-panel::button>

                @if($resetUrl)
                    <x-admin-panel::button :href="$resetUrl" size="sm" variant="ghost" data-admin-ajax-link>
                        {{ admin_trans('Reset') }}
                    </x-admin-panel::button>
                @endif
            </div>
        </div>

        @if($hasAdvanced)
            <div class="admin-filter-shell-advanced @if($advancedOpen) is-open @endif" data-admin-filter-advanced>
                <div class="admin-filter-shell-advanced-grid">
                    {{ $advanced }}
                </div>
            </div>
        @endif
    </form>

    @if($hasSaved)
        <div class="admin-filter-shell-saved" data-admin-filter-saved>
            <div class="admin-filter-shell-saved-inner">
                {{ $saved }}
            </div>
        </div>
    @endif
</x-admin-panel::card>
