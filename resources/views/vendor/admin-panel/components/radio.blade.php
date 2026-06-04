@props([
    'name' => '',
    'label' => null,
    'value' => '',
    'checked' => false,
    'disabled' => false,
    'help' => null,
])

@php
    $inputId = $attributes->get('id', $name . '-' . $value . '-' . uniqid());
    $isChecked = old($name) == $value || $checked;
    $translatedLabel = $label ? admin_trans($label) : null;
    $translatedHelp = $help ? admin_trans($help) : null;

    $attributes = $attributes->merge([
        'type' => 'radio',
        'name' => $name,
        'id' => $inputId,
        'value' => $value,
        'disabled' => $disabled,
    ])->class(['form-check-input']);

    if ($isChecked) {
        $attributes = $attributes->merge(['checked' => true]);
    }
@endphp

<div class="form-check">
    <input {{ $attributes }}>

    @if($label)
        <label for="{{ $inputId }}" class="form-check-label">
            {{ $translatedLabel }}
        </label>
    @endif

    @if($help)
        <small class="form-help d-block ml-6">{{ $translatedHelp }}</small>
    @endif

    @error($name)
        <small class="form-error d-block ml-6">{{ $message }}</small>
    @enderror
</div>
