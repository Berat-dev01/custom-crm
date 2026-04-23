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
            <form method="POST" action="{{ route('crm.'.$module.'.import.preview') }}" enctype="multipart/form-data" class="crm-stack" data-crm-import-form data-crm-import-preview-url="{{ route('crm.'.$module.'.import.preview') }}">
                @csrf
                <x-admin-panel::input name="file" label="CSV or XLSX File" type="file" required />
                <div class="crm-row-actions">
                    <x-admin-panel::button type="submit" icon="eye" data-crm-preview-btn>Preview</x-admin-panel::button>
                    <x-admin-panel::button type="submit" formaction="{{ route('crm.'.$module.'.import.store') }}" icon="upload">
                        Import
                    </x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        <div data-crm-import-preview>
            @if(session('crm_import_preview'))
                @include('crm::admin.data-transfer._preview', ['preview' => session('crm_import_preview')])
            @endif
        </div>

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
