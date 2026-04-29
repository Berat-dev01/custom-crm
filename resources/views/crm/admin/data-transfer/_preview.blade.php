<x-admin-panel::card>
    <x-slot:header>{{ __('Preview') }}</x-slot:header>
    <div class="crm-import-summary">
        <div>
            <span class="crm-import-summary-value">{{ $preview['summary']['total_rows'] }}</span>
            <span class="crm-muted">{{ __('total rows') }}</span>
        </div>
        <div>
            <span class="crm-import-summary-value">{{ $preview['summary']['shown_rows'] }}</span>
            <span class="crm-muted">{{ __('shown') }}</span>
        </div>
        <div>
            <span class="crm-import-summary-value crm-import-valid">{{ $preview['summary']['valid_rows'] }}</span>
            <span class="crm-muted">{{ __('valid in preview') }}</span>
        </div>
        <div>
            <span class="crm-import-summary-value crm-import-invalid">{{ $preview['summary']['invalid_rows'] }}</span>
            <span class="crm-muted">{{ __('invalid in preview') }}</span>
        </div>
    </div>

    @if(!empty($preview['missing_headers']))
        <x-admin-panel::alert variant="warning">
            {{ __('Missing columns: :headers. Defaults are used where possible; required missing values will be reported as validation errors.', ['headers' => implode(', ', $preview['missing_headers'])]) }}
        </x-admin-panel::alert>
    @endif

    @if(!empty($preview['unexpected_headers']))
        <p class="crm-muted">{{ __('Extra columns will be ignored: :headers', ['headers' => implode(', ', $preview['unexpected_headers'])]) }}</p>
    @endif

    <div class="crm-import-preview-table">
        <x-admin-panel::table :headers="array_merge($preview['headers'], [__('Status')])">
            @foreach($preview['rows'] as $row)
                <tr class="{{ $row['valid'] ? '' : 'crm-import-row-invalid' }}">
                    @foreach($preview['headers'] as $header)
                        <td>
                            <span>{{ $row['values'][$header] ?? '-' }}</span>
                        </td>
                    @endforeach
                    <td>
                        @if($row['valid'])
                            <x-admin-panel::badge variant="success">{{ __('Valid') }}</x-admin-panel::badge>
                        @else
                            <x-admin-panel::badge variant="danger">{{ __('Error') }}</x-admin-panel::badge>
                            <div class="crm-import-errors">
                                @foreach($row['errors'] as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-admin-panel::table>
    </div>
</x-admin-panel::card>
