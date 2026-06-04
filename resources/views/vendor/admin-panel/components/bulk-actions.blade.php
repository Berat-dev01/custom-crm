@props([
    'form',
    'checkboxSelector' => 'input[type=checkbox]:checked',
    'label' => 'records',
])

<div
    class="admin-bulk-actions"
    data-admin-bulk-actions
    data-form-id="{{ $form }}"
    data-checkbox-selector="{{ $checkboxSelector }}"
    hidden
>
    <div class="admin-bulk-actions-summary">
        <strong data-admin-bulk-count>0</strong>
        <span>{{ admin_trans($label) }} {{ admin_trans('selected') }}</span>
    </div>

    <div class="admin-bulk-actions-controls">
        {{ $slot }}
    </div>
</div>
