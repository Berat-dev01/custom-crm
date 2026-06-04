@props([
    'url',
    'module',
    'columns' => [],
    'formats' => ['csv'],
    'label' => 'Export',
])

@php
    $formatLabels = ['csv' => 'CSV', 'excel' => 'Excel (.xlsx)'];
    $scopeName = 'export-scope-' . $module;
    $moduleLabel = app()->bound(\Sanalkopru\Crm\Support\CrmLabelCatalog::class)
        ? app(\Sanalkopru\Crm\Support\CrmLabelCatalog::class)->moduleLabel($module)
        : \Illuminate\Support\Str::headline($module);

    $defaultColumns = array_values(array_filter($columns, fn ($c) => $c['default']));
    $extraColumns   = array_values(array_filter($columns, fn ($c) => ! $c['default']));
    $orderedColumns = array_merge($defaultColumns, $extraColumns);
@endphp

<div
    data-admin-export-button
    data-export-module="{{ $module }}"
    {{ $attributes }}
>
    <x-admin-panel::button
        type="button"
        variant="outline"
        icon="download"
        data-export-trigger
    >{{ $label }}</x-admin-panel::button>

    <div class="modal-backdrop" data-export-backdrop hidden>
        <div class="modal modal-lg" data-export-modal @click.stop role="dialog" aria-modal="true">

            <div class="modal-header">
                <h3 class="modal-title">{{ admin_trans('Export :module', ['module' => $moduleLabel]) }}</h3>
                <button type="button" data-export-close class="btn btn-ghost btn-icon btn-sm" aria-label="{{ admin_trans('Close') }}">
                    <i data-lucide="x" width="16" height="16"></i>
                </button>
            </div>

            <div class="modal-body export-modal-body">

                {{-- Scope --}}
                <div class="export-section">
                    <span class="export-section-label">{{ admin_trans('Export scope') }}</span>
                    <div class="export-scope-options">
                        <label class="export-scope-option" data-export-scope-selected hidden>
                            <input type="radio" name="{{ $scopeName }}" value="selected">
                            <span>{{ admin_trans('Export :count selected rows', ['count' => '']) }}<strong data-export-selected-count>0</strong>{{ admin_trans('selected rows suffix') }}</span>
                        </label>
                        <label class="export-scope-option">
                            <input type="radio" name="{{ $scopeName }}" value="filtered" checked>
                            <span>{{ admin_trans('Export all') }} <strong data-export-total-count>—</strong> {{ admin_trans('filtered results') }}</span>
                        </label>
                    </div>
                </div>

                {{-- Format --}}
                <div class="export-section">
                    <span class="export-section-label">{{ admin_trans('Format') }}</span>
                    <div class="export-format-tabs">
                        @foreach($formats as $i => $format)
                            <button
                                type="button"
                                class="export-format-tab {{ $i === 0 ? 'is-active' : '' }}"
                                data-export-format="{{ $format }}"
                            >{{ $formatLabels[$format] ?? strtoupper($format) }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Columns --}}
                <div class="export-section">
                    <div class="export-columns-header">
                        <span class="export-section-label">{{ admin_trans('Columns') }}</span>
                        <div class="export-columns-actions">
                            <button type="button" class="btn btn-link btn-xs" data-export-check-all>{{ admin_trans('All') }}</button>
                            <button type="button" class="btn btn-link btn-xs" data-export-check-none>{{ admin_trans('None') }}</button>
                        </div>
                    </div>
                    <ul class="export-column-list" data-export-column-list>
                        @foreach($orderedColumns as $column)
                            <li
                                class="export-column-item"
                                draggable="true"
                                data-column-key="{{ $column['key'] }}"
                            >
                                <span class="export-drag-handle" aria-hidden="true">
                                    <i data-lucide="grip-vertical" width="14" height="14"></i>
                                </span>
                                <label class="export-column-label">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        {{ $column['default'] ? 'checked' : '' }}
                                    >
                                    <span>{{ admin_trans($column['label']) }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>

            <div class="modal-footer">
                <form method="POST" action="{{ $url }}" data-export-form>
                    @csrf
                </form>
                <button type="button" class="btn btn-secondary" data-export-close>{{ admin_trans('Cancel') }}</button>
                <button type="button" class="btn btn-primary" data-export-submit>
                    <i data-lucide="download" width="14" height="14"></i>
                    {{ admin_trans('Export') }}
                </button>
            </div>

        </div>
    </div>
</div>
