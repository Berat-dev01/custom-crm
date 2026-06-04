@props([
    'name' => 'images[]',
    'multiple' => true,
    'accept' => 'image/*',
    'maxSize' => 10,        // MB
    'maxFiles' => 10,
    'withPrimary' => false,
    'withSort' => false,
    'help' => null,
])

@php
    $uniqueId = 'dropzone-' . uniqid();
    $translatedHelp = $help ? admin_trans($help) : null;
    $dropzoneText = [
        'primary' => admin_trans('Primary'),
        'setPrimary' => admin_trans('Set Primary'),
        'remove' => admin_trans('Remove'),
    ];
@endphp

<div class="admin-dropzone-container">
    {{-- Dropzone Area --}}
    <div id="{{ $uniqueId }}" class="admin-dropzone">
        <svg class="admin-dropzone-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <div class="admin-dropzone-text">{{ admin_trans('Drag & drop or click to upload') }}</div>
        <div class="admin-dropzone-hint">
            {{ $accept }} ({{ admin_trans('Max') }} {{ $maxSize }}MB{{ $multiple ? ', ' . admin_trans('up to') . ' ' . $maxFiles . ' ' . admin_trans('files') : '' }})
        </div>
    </div>

    {{-- Preview Grid --}}
    <div id="{{ $uniqueId }}-previews" class="admin-dropzone-previews"></div>

    {{-- Hidden Inputs Container --}}
    <div id="{{ $uniqueId }}-inputs" style="display: none;"></div>

    @if($help)
        <div class="admin-dropzone-help">{{ $translatedHelp }}</div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    (function() {
    const dropzoneId = '{{ $uniqueId }}';
    const name = '{{ $name }}';
    const multiple = {{ $multiple ? 'true' : 'false' }};
    const maxSize = {{ $maxSize }};
    const maxFiles = {{ $maxFiles }};
    const withPrimary = {{ $withPrimary ? 'true' : 'false' }};
    const withSort = {{ $withSort ? 'true' : 'false' }};
    const accept = '{{ $accept }}';
    const dropzoneText = @json($dropzoneText);

    // Wait for DOM and Dropzone to be ready
    if (typeof Dropzone === 'undefined') {
        console.error('Dropzone.js is not loaded');
        return;
    }

    const dropzoneEl = document.getElementById(dropzoneId);
    const previewsEl = document.getElementById(dropzoneId + '-previews');
    const inputsEl = document.getElementById(dropzoneId + '-inputs');

    if (!dropzoneEl) return;

    let files = [];
    let primaryIndex = 0;

    // Initialize Dropzone
    const dz = new Dropzone(dropzoneEl, {
        url: '#', // Dummy URL - we handle upload on form submit
        autoProcessQueue: false,
        uploadMultiple: false,
        maxFilesize: maxSize,
        maxFiles: multiple ? maxFiles : 1,
        acceptedFiles: accept,
        addRemoveLinks: false,
        dictDefaultMessage: '',
        previewsContainer: false, // We'll create custom previews
    });

    // On file added
    dz.on('addedfile', function(file) {
        const index = files.length;
        files.push(file);

        // Set first file as primary
        if (withPrimary && index === 0) {
            primaryIndex = 0;
        }

        renderPreviews();
        updateHiddenInputs();
    });

    // On file removed by Dropzone (e.g., exceeds limit)
    dz.on('removedfile', function(file) {
        const index = files.indexOf(file);
        if (index > -1) {
            files.splice(index, 1);
            renderPreviews();
            updateHiddenInputs();
        }
    });

    // Error handling
    dz.on('error', function(file, message) {
        console.error('Dropzone error:', message);
        if (typeof showToast !== 'undefined') {
            showToast(message, 'error');
        } else {
            alert(message);
        }
        dz.removeFile(file);
    });

    // Render preview cards
    function renderPreviews() {
        previewsEl.innerHTML = '';

        files.forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'admin-dropzone-preview';
            if (withPrimary && index === primaryIndex) {
                card.classList.add('is-primary');
            }
            card.dataset.index = index;

            // Primary badge
            if (withPrimary && index === primaryIndex) {
                const badge = document.createElement('div');
                badge.className = 'admin-dropzone-preview-badge';
                badge.textContent = dropzoneText.primary;
                card.appendChild(badge);
            }

            // Thumbnail
            const thumb = document.createElement('img');
            thumb.className = 'admin-dropzone-preview-thumb';
            thumb.alt = file.name;

            // Create thumbnail from file
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    thumb.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                thumb.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"%3E%3Cpath d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"%3E%3C/path%3E%3Cpolyline points="14 2 14 8 20 8"%3E%3C/polyline%3E%3C/svg%3E';
            }
            card.appendChild(thumb);

            // Info section
            const info = document.createElement('div');
            info.className = 'admin-dropzone-preview-info';

            const nameDiv = document.createElement('div');
            nameDiv.className = 'admin-dropzone-preview-name';
            nameDiv.textContent = file.name;
            info.appendChild(nameDiv);

            // Actions
            const actions = document.createElement('div');
            actions.className = 'admin-dropzone-preview-actions';

            if (withPrimary) {
                const primaryBtn = document.createElement('button');
                primaryBtn.type = 'button';
                primaryBtn.className = 'admin-dropzone-preview-btn';
                if (index === primaryIndex) {
                    primaryBtn.className += ' btn-primary-action';
                    primaryBtn.textContent = dropzoneText.primary;
                } else {
                    primaryBtn.textContent = dropzoneText.setPrimary;
                }
                primaryBtn.onclick = function() {
                    setPrimary(index);
                };
                actions.appendChild(primaryBtn);
            }

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'admin-dropzone-preview-btn btn-danger';
            removeBtn.textContent = dropzoneText.remove;
            removeBtn.onclick = function() {
                removeFile(index);
            };
            actions.appendChild(removeBtn);

            info.appendChild(actions);
            card.appendChild(info);

            previewsEl.appendChild(card);
        });

        // Initialize SortableJS if enabled
        if (withSort && files.length > 0 && typeof Sortable !== 'undefined') {
            new Sortable(previewsEl, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function(evt) {
                    // Reorder files array
                    const item = files.splice(evt.oldIndex, 1)[0];
                    files.splice(evt.newIndex, 0, item);

                    // Update primary index if primary was moved
                    if (primaryIndex === evt.oldIndex) {
                        primaryIndex = evt.newIndex;
                    } else if (evt.oldIndex < primaryIndex && evt.newIndex >= primaryIndex) {
                        primaryIndex--;
                    } else if (evt.oldIndex > primaryIndex && evt.newIndex <= primaryIndex) {
                        primaryIndex++;
                    }

                    renderPreviews();
                    updateHiddenInputs();
                }
            });
        }
    }

    // Set primary image
    function setPrimary(index) {
        primaryIndex = index;
        renderPreviews();
        updateHiddenInputs();
    }

    // Remove file
    function removeFile(index) {
        files.splice(index, 1);
        dz.removeFile(dz.files[index]);

        // Adjust primary index if needed
        if (primaryIndex === index) {
            primaryIndex = 0;
        } else if (primaryIndex > index) {
            primaryIndex--;
        }

        renderPreviews();
        updateHiddenInputs();
    }

    // Update hidden inputs for form submission
    function updateHiddenInputs() {
        inputsEl.innerHTML = '';

        // Create file inputs using DataTransfer API
        files.forEach((file, index) => {
            const dt = new DataTransfer();
            dt.items.add(file);

            const input = document.createElement('input');
            input.type = 'file';
            input.name = name;
            input.files = dt.files;
            input.style.display = 'none';
            inputsEl.appendChild(input);
        });

        // Primary index input
        if (withPrimary) {
            const primaryInput = document.createElement('input');
            primaryInput.type = 'hidden';
            primaryInput.name = name.replace('[]', '') + '_primary_index';
            primaryInput.value = primaryIndex;
            inputsEl.appendChild(primaryInput);
        }

        // Order inputs
        if (withSort) {
            files.forEach((file, index) => {
                const orderInput = document.createElement('input');
                orderInput.type = 'hidden';
                orderInput.name = name.replace('[]', '') + '_order[]';
                orderInput.value = index;
                inputsEl.appendChild(orderInput);
            });
        }
    }
})();
})
</script>
