@props([
    'variant' => 'info',     // success, danger, warning, info
    'dismissible' => false,
    'message' => null,
    'icon' => null,          // Custom icon, or auto-detect from variant
    'autoDismiss' => null,   // Auto-dismiss after X milliseconds
])

@php
    // Auto-detect icon based on variant
    if (!$icon) {
        $icon = match($variant) {
            'success' => 'check-circle',
            'danger' => 'alert-circle',
            'warning' => 'alert-triangle',
            'info' => 'info',
            default => 'info',
        };
    }

    $alertClasses = "alert alert-{$variant}";
    if ($dismissible) {
        $alertClasses .= ' alert-dismissible';
    }

    $attributes = $attributes->class([$alertClasses]);

    $alpineData = $dismissible || $autoDismiss
        ? "x-data=\"{ show: true }\" x-show=\"show\""
        : '';

    $autoHide = $autoDismiss
        ? "x-init=\"setTimeout(() => show = false, {$autoDismiss})\""
        : '';
@endphp

@if($message || !$slot->isEmpty())
    <div
        {{ $attributes }}
        @if($alpineData) {!! $alpineData !!} @endif
        @if($autoHide) {!! $autoHide !!} @endif
        role="alert"
    >
        <div class="d-flex items-start gap-3 flex-1">
            <i data-lucide="{{ $icon }}" width="18" height="18"></i>
            <div class="flex-1">
                @if($message)
                    {{ $message }}
                @else
                    {{ $slot }}
                @endif
            </div>
        </div>

        @if($dismissible)
            <button
                type="button"
                class="alert-close"
                @click="show = false"
                aria-label="{{ admin_trans('Close') }}"
            >
                <i data-lucide="x" width="16" height="16"></i>
            </button>
        @endif
    </div>
@endif
