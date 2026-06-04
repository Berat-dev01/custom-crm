@props([
    'collapsible' => false,
    'collapsed' => false,
])

@php
    $cardId = $attributes->get('id', 'card-' . uniqid());

    $alpineData = $collapsible
        ? "x-data=\"{ collapsed: " . ($collapsed ? 'true' : 'false') . " }\""
        : '';

    $attributes = $attributes->class(['card'])->merge([
        'id' => $cardId,
    ]);

    $headerHtml = isset($header) ? trim($header->toHtml()) : null;
    $headerIsPlainText = $headerHtml !== null && $headerHtml !== '' && $headerHtml === strip_tags($headerHtml);
    $headerText = $headerIsPlainText ? admin_trans($headerHtml) : null;
@endphp

<div {{ $attributes }} @if($alpineData) {!! $alpineData !!} @endif>
    @if(isset($header))
        <div class="card-header">
            @if($collapsible)
                <button
                    type="button"
                    class="d-flex items-center justify-between w-full text-left"
                    @click="collapsed = !collapsed"
                    style="background: none; border: none; padding: 0; cursor: pointer;"
                >
                    <h3 class="card-title">
                        @if($headerIsPlainText)
                            {{ $headerText }}
                        @else
                            {{ $header }}
                        @endif
                    </h3>
                    <i data-lucide="chevron-down" width="16" height="16" x-show="!collapsed"></i>
                    <i data-lucide="chevron-up" width="16" height="16" x-show="collapsed" style="display: none;"></i>
                </button>
            @else
                <h3 class="card-title">
                    @if($headerIsPlainText)
                        {{ $headerText }}
                    @else
                        {{ $header }}
                    @endif
                </h3>
            @endif

            @if(isset($headerActions))
                <div class="card-header-actions">
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    @endif

    <div
        @if($collapsible)
            x-show="!collapsed"
        @endif
    >
        @if(isset($body))
            <div class="card-body">
                {{ $body }}
            </div>
        @else
            <div class="card-body">
                {{ $slot }}
            </div>
        @endif

        @if(isset($footer))
            <div class="card-footer">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
