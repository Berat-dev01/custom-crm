@props([
    'name' => '',
    'label' => null,
    'options' => [],         // Array of options ['value' => 'label'] or array of objects
    'selected' => null,
    'placeholder' => null,   // If provided, adds empty option
    'required' => false,
    'help' => null,
    'size' => 'md',          // sm, md, lg
    'disabled' => false,
    'valueField' => 'id',    // For object arrays
    'labelField' => 'name',  // For object arrays
    'searchable' => true,
    'clearable' => true,
    'custom' => true,
    'groupClass' => null,    // Extra CSS class for the outer form-group div
])

@php
    $inputId = $attributes->get('id', $name);
    $oldKey = str_ends_with($name, '[]') ? substr($name, 0, -2) : $name;
    $selectedValue = old($oldKey, old($name, $selected ?? ''));
    $selectedValues = is_array($selectedValue) ? array_map('strval', $selectedValue) : [(string) $selectedValue];
    $hasError = $errors->has($name);
    $translatedLabel = $label ? admin_trans($label) : null;
    $translatedPlaceholder = $placeholder ? admin_trans($placeholder) : null;
    $translatedHelp = $help ? admin_trans($help) : null;
    $isMultiple = $attributes->has('multiple') || str_ends_with($name, '[]');

    $baseClass = 'form-control';
    $sizeClass = $size !== 'md' ? 'form-control-' . $size : '';
    $errorClass = $hasError ? 'is-invalid' : '';

    $selectClasses = trim("{$baseClass} {$sizeClass} {$errorClass}");

    $selectAttributes = $attributes->merge([
        'name' => $name,
        'id' => $inputId,
        'disabled' => $disabled,
    ])->class([$selectClasses]);

    if ($custom) {
        $selectAttributes = $selectAttributes->merge([
            'data-admin-select-native' => true,
        ]);
    }

    if ($required) {
        $selectAttributes = $selectAttributes->merge(['required' => true]);
    }
@endphp

<div
    class="form-group {{ $groupClass }}"
    @if($custom)
        data-admin-select
        data-admin-select-placeholder="{{ $translatedPlaceholder ?: admin_trans('Select an option') }}"
        data-admin-select-searchable="{{ $searchable ? '1' : '0' }}"
        data-admin-select-clearable="{{ $clearable ? '1' : '0' }}"
    @endif
>
    @if($label)
        <label for="{{ $inputId }}" class="form-label @if($required) required @endif">
            {{ $translatedLabel }}
        </label>
    @endif

    <select {{ $selectAttributes }}>
        @if($placeholder)
            <option value="">{{ $translatedPlaceholder }}</option>
        @endif

        @foreach($options as $key => $option)
            @if(is_object($option))
                <option
                    value="{{ $option->{$valueField} }}"
                    @if(in_array((string) $option->{$valueField}, $selectedValues, true)) selected @endif
                >
                    {{ $option->{$labelField} ?? $option->{$valueField} }}
                </option>
            @elseif(is_array($option))
                <option
                    value="{{ $option[$valueField] }}"
                    @if(in_array((string) $option[$valueField], $selectedValues, true)) selected @endif
                >
                    {{ $option[$labelField] ?? $option[$valueField] }}
                </option>
            @else
                <option
                    value="{{ $key }}"
                    @if(in_array((string) $key, $selectedValues, true)) selected @endif
                >
                    {{ admin_trans($option) }}
                </option>
            @endif
        @endforeach
    </select>

    @if($help && !$hasError)
        <small class="form-help">{{ $translatedHelp }}</small>
    @endif

    @error($name)
        <small class="form-error">{{ $message }}</small>
    @enderror
</div>
