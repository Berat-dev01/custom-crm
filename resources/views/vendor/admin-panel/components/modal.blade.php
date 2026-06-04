@props([
    'name' => 'modal',
    'title' => '',
    'size' => 'md',          // sm, md, lg, xl
    'showFooter' => true,
])

@php
    $modalId = $attributes->get('id', $name . '-' . uniqid());

    $maxWidth = match($size) {
        'sm' => '400px',
        'lg' => '700px',
        'xl' => '900px',
        default => '500px', // md
    };
@endphp

<div x-data="{ open: false }" {{ $attributes }}>
    <!-- Trigger Slot (optional) -->
    @if(isset($trigger))
        <div @click="open = true">
            {{ $trigger }}
        </div>
    @endif

    <!-- Modal Backdrop & Content -->
    <div
        x-show="open"
        @click.self="open = false"
        @keydown.escape.window="open = false"
        class="modal-backdrop"
        style="display: none;"
    >
        <div
            class="modal"
            style="max-width: {{ $maxWidth }};"
            @click.stop
        >
            <!-- Header -->
            <div class="modal-header">
                <h3 class="modal-title">{{ $title }}</h3>
                <button
                    type="button"
                    @click="open = false"
                    class="btn btn-ghost btn-icon btn-sm"
                    aria-label="{{ admin_trans('Close') }}"
                >
                    <i data-lucide="x" width="16" height="16"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                {{ $slot }}
            </div>

            <!-- Footer -->
            @if($showFooter)
                <div class="modal-footer">
                    @if(isset($footer))
                        {{ $footer }}
                    @else
                        <button type="button" @click="open = false" class="btn btn-secondary">
                            {{ admin_trans('Cancel') }}
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
