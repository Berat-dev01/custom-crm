@extends('admin-panel::layouts.app')

@section('title', 'Import '.Str::headline($module))
@section('page-title', 'Import '.Str::headline($module))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="{{ $module }}">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / {{ Str::headline($module) }}</p>
                <h1>Import {{ Str::headline($module) }}</h1>
            </div>
            <div class="crm-admin-actions">
                <x-admin-panel::button :href="route('crm.'.$module.'.template')" variant="outline" icon="download">
                    Template
                </x-admin-panel::button>
                <x-admin-panel::button :href="route('crm.'.$module.'.index')" variant="ghost" icon="arrow-left">
                    Back
                </x-admin-panel::button>
            </div>
        </header>

        <x-admin-panel::card>
            <x-slot:header>Column Standard</x-slot:header>
            <p class="crm-muted">{{ implode(', ', $headers) }}</p>
        </x-admin-panel::card>

        <x-admin-panel::card>
            <form method="POST" action="{{ route('crm.'.$module.'.import.preview') }}" enctype="multipart/form-data" class="crm-stack">
                @csrf
                <x-admin-panel::input name="file" label="CSV or XLSX File" type="file" required />
                <div class="crm-row-actions">
                    <x-admin-panel::button type="submit" icon="eye">Preview</x-admin-panel::button>
                    <x-admin-panel::button type="submit" formaction="{{ route('crm.'.$module.'.import.store') }}" icon="upload">
                        Import
                    </x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        @if(session('crm_import_preview'))
            <x-admin-panel::card>
                <x-slot:header>Preview</x-slot:header>
                @php($preview = session('crm_import_preview'))
                <div class="crm-import-summary">
                    <div>
                        <span class="crm-import-summary-value">{{ $preview['summary']['total_rows'] }}</span>
                        <span class="crm-muted">total rows</span>
                    </div>
                    <div>
                        <span class="crm-import-summary-value">{{ $preview['summary']['shown_rows'] }}</span>
                        <span class="crm-muted">shown</span>
                    </div>
                    <div>
                        <span class="crm-import-summary-value crm-import-valid">{{ $preview['summary']['valid_rows'] }}</span>
                        <span class="crm-muted">valid in preview</span>
                    </div>
                    <div>
                        <span class="crm-import-summary-value crm-import-invalid">{{ $preview['summary']['invalid_rows'] }}</span>
                        <span class="crm-muted">invalid in preview</span>
                    </div>
                </div>

                @if(!empty($preview['missing_headers']))
                    <x-admin-panel::alert variant="warning">
                        Missing columns: {{ implode(', ', $preview['missing_headers']) }}. Defaults are used where possible; required missing values will be reported as validation errors.
                    </x-admin-panel::alert>
                @endif

                @if(!empty($preview['unexpected_headers']))
                    <p class="crm-muted">Extra columns will be ignored: {{ implode(', ', $preview['unexpected_headers']) }}</p>
                @endif

                <x-admin-panel::table :headers="array_merge($preview['headers'], ['Status'])">
                    @foreach($preview['rows'] as $row)
                        <tr class="{{ $row['valid'] ? '' : 'crm-import-row-invalid' }}">
                            @foreach($preview['headers'] as $header)
                                <td>
                                    <span>{{ $row['values'][$header] ?? '-' }}</span>
                                </td>
                            @endforeach
                            <td>
                                @if($row['valid'])
                                    <x-admin-panel::badge variant="success">Valid</x-admin-panel::badge>
                                @else
                                    <x-admin-panel::badge variant="danger">Error</x-admin-panel::badge>
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
            </x-admin-panel::card>
        @endif

        @if(session('crm_import_result'))
            <x-admin-panel::card>
                <x-slot:header>Import Report</x-slot:header>
                @php($result = session('crm_import_result'))
                <p>{{ $result['created'] }} created, {{ $result['failed'] }} failed.</p>
                @if(!empty($result['error_report_url']))
                    <x-admin-panel::button :href="$result['error_report_url']" variant="outline" icon="download">
                        Download Error Report
                    </x-admin-panel::button>
                @endif
                @if(!empty($result['errors']))
                    <x-admin-panel::table :headers="['Row', 'Error']">
                        @foreach($result['errors'] as $error)
                            <tr>
                                <td>{{ $error['row'] }}</td>
                                <td>{{ $error['message'] }}</td>
                            </tr>
                        @endforeach
                    </x-admin-panel::table>
                @endif
            </x-admin-panel::card>
        @endif
    </section>
@endsection
