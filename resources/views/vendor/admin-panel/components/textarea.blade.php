@props([
    'name' => '',
    'label' => null,
    'rows' => 4,
    'placeholder' => '',
    'required' => false,
    'help' => null,
    'value' => null,
    'disabled' => false,
    'maxlength' => null,
    'showCount' => false,     // Show character counter
])

@php
    $inputId = $attributes->get('id', $name);
    $textValue = old($name, $value ?? '');
    $hasError = $errors->has($name);
    $translatedLabel = $label ? admin_trans($label) : null;
    $translatedPlaceholder = $placeholder ? admin_trans($placeholder) : '';
    $translatedHelp = $help ? admin_trans($help) : null;

    $baseClass = 'form-control';
    $errorClass = $hasError ? 'is-invalid' : '';

    $textareaClasses = trim("{$baseClass} {$errorClass}");

    $textareaAttributes = $attributes->merge([
        'name' => $name,
        'id' => $inputId,
        'rows' => $rows,
        'placeholder' => $translatedPlaceholder,
        'disabled' => $disabled,
    ])->class([$textareaClasses]);

    if ($required) {
        $textareaAttributes = $textareaAttributes->merge(['required' => true]);
    }

    if ($maxlength) {
        $textareaAttributes = $textareaAttributes->merge(['maxlength' => $maxlength]);
    }

    $alpineData = $showCount && $maxlength
        ? "x-data=\"{ count: " . strlen($textValue) . ", max: {$maxlength} }\""
        : '';

    $alpineModel = $showCount && $maxlength ? 'x-model="count"' : '';
@endphp

<div class="form-group" @if($alpineData) {!! $alpineData !!} @endif>
    @if($label)
        <label for="{{ $inputId }}" class="form-label @if($required) required @endif">
            {{ $translatedLabel }}
            @if($showCount && $maxlength)
                <span class="text-xs text-muted ml-2" x-text="`${count}/${max}`"></span>
            @endif
        </label>
    @endif

    <textarea
        {{ $textareaAttributes }}
        @if($showCount && $maxlength) x-on:input="count = $el.value.length" @endif
    >{{ $textValue }}</textarea>

    @if($help && !$hasError)
        <small class="form-help">{{ $translatedHelp }}</small>
    @endif

    @error($name)
        <small class="form-error">{{ $message }}</small>
    @enderror
</div>
