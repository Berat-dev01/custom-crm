@props([
    'tabs' => [],            // Array of tabs: [['id' => 'tab1', 'label' => 'Tab 1', 'icon' => 'home'], ...]
    'active' => null,        // Active tab ID
])

@php
    $activeTab = $active ?? ($tabs[0]['id'] ?? 'tab-1');
@endphp

<div x-data="{ activeTab: '{{ $activeTab }}' }" {{ $attributes }}>
    <!-- Tab Navigation -->
    <div class="border-b border-color mb-4">
        <nav class="d-flex gap-4" role="tablist">
            @foreach($tabs as $tab)
                <button
                    type="button"
                    @click="activeTab = '{{ $tab['id'] }}'"
                    :class="{ 'border-primary text-primary': activeTab === '{{ $tab['id'] }}', 'border-transparent text-secondary': activeTab !== '{{ $tab['id'] }}' }"
                    class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors"
                    style="background: none; cursor: pointer;"
                    role="tab"
                    :aria-selected="activeTab === '{{ $tab['id'] }}'"
                >
                    @if(isset($tab['icon']))
                        <i data-lucide="{{ $tab['icon'] }}" width="16" height="16" class="mr-2"></i>
                    @endif
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    <!-- Tab Panels -->
    <div>
        {{ $slot }}
    </div>
</div>
