@props([
    'name' => '',
    'label' => null,
    'type' => 'text',
    'placeholder' => '',
    'required' => false,
    'help' => null,
    'icon' => null,          // Lucide icon name for prefix
    'value' => null,
    'size' => 'md',          // sm, md, lg
    'disabled' => false,
])

@php
    $inputId = $attributes->get('id', $name);
    $inputValue = old($name, $value ?? '');
    $hasError = $errors->has($name);
    $translatedLabel = $label ? admin_trans($label) : null;
    $translatedPlaceholder = $placeholder ? admin_trans($placeholder) : '';
    $translatedHelp = $help ? admin_trans($help) : null;

    $baseClass = 'form-control';
    $sizeClass = $size !== 'md' ? 'form-control-' . $size : '';
    $errorClass = $hasError ? 'is-invalid' : '';

    $inputClasses = trim("{$baseClass} {$sizeClass} {$errorClass}");

    $inputAttributes = $attributes->merge([
        'type' => $type,
        'name' => $name,
        'id' => $inputId,
        'value' => $inputValue,
        'placeholder' => $translatedPlaceholder,
        'disabled' => $disabled,
    ])->class([$inputClasses]);

    if ($required) {
        $inputAttributes = $inputAttributes->merge(['required' => true]);
    }
@endphp

<div class="form-group">
    @if($label)
        <label for="{{ $inputId }}" class="form-label @if($required) required @endif">
            {{ $translatedLabel }}
        </label>
    @endif

    @if($icon)
        <div class="input-group">
            <span class="input-group-addon">
                <i data-lucide="{{ $icon }}" width="16" height="16"></i>
            </span>
            <input {{ $inputAttributes }}>
        </div>
    @else
        <input {{ $inputAttributes }}>
    @endif

    @if($help && !$hasError)
        <small class="form-help">{{ $translatedHelp }}</small>
    @endif

    @error($name)
        <small class="form-error">{{ $message }}</small>
    @enderror
</div>
