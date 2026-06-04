@props([
    'name' => 'file',
    'label' => null,
    'accept' => null,        // e.g., 'image/*', '.pdf,.doc'
    'multiple' => false,
    'preview' => false,      // Show image preview
    'required' => false,
    'help' => null,
    'maxSize' => null,       // Max file size in MB
])

@php
    $inputId = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $translatedLabel = $label ? admin_trans($label) : null;
    $translatedHelp = $help ? admin_trans($help) : null;

    $attributes = $attributes->merge([
        'type' => 'file',
        'name' => $name,
        'id' => $inputId,
        'accept' => $accept,
    ])->class(['form-control', 'is-invalid' => $hasError]);

    if ($multiple) {
        $attributes = $attributes->merge(['multiple' => true]);
    }

    if ($required) {
        $attributes = $attributes->merge(['required' => true]);
    }

    $alpineData = $preview
        ? "x-data=\"{ preview: null, fileName: '' }\""
        : "x-data=\"{ fileName: '' }\"";
@endphp

<div class="form-group" {{ $alpineData }}>
    @if($label)
        <label for="{{ $inputId }}" class="form-label @if($required) required @endif">
            {{ $translatedLabel }}
        </label>
    @endif

    <div class="d-flex gap-3">
        <input
            {{ $attributes }}
            @change="
                const file = $event.target.files[0];
                if (file) {
                    fileName = file.name;
                    @if($preview)
                        const reader = new FileReader();
                        reader.onload = (e) => preview = e.target.result;
                        reader.readAsDataURL(file);
                    @endif
                }
            "
        >

        @if($preview)
            <div
                x-show="preview"
                style="display: none;"
                class="border rounded p-2"
            >
                <img
                    :src="preview"
                    alt="{{ admin_trans('Preview') }}"
                    class="rounded"
                    style="max-width: 200px; max-height: 200px; object-fit: cover;"
                >
            </div>
        @endif
    </div>

    <div x-show="fileName" style="display: none;" class="mt-2 text-sm text-secondary">
        {{ admin_trans('Selected') }}: <span x-text="fileName"></span>
    </div>

    @if($help && !$hasError)
        <small class="form-help">
            {{ $translatedHelp }}
            @if($maxSize)
                ({{ admin_trans('Max') }}: {{ $maxSize }}MB)
            @endif
        </small>
    @endif

    @error($name)
        <small class="form-error">{{ $message }}</small>
    @enderror
</div>
