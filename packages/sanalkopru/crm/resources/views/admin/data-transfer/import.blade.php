@extends('admin-panel::layouts.app')

@php($moduleLabel = $crmFormat->module($module))

@section('title', __('Import').' '.$moduleLabel)
@section('page-title', __('Import').' '.$moduleLabel)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="{{ $module }}">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / {{ $moduleLabel }}</p>
                <h1>{{ __('Import') }} {{ $moduleLabel }}</h1>
            </div>
            <div class="crm-admin-actions">
                <x-admin-panel::button :href="route('crm.'.$module.'.template')" variant="outline" icon="download">
                    {{ __('Template') }}
                </x-admin-panel::button>
                <x-admin-panel::button :href="route('crm.'.$module.'.index')" variant="ghost" icon="arrow-left">
                    {{ __('Back') }}
                </x-admin-panel::button>
            </div>
        </header>

        <x-admin-panel::card>
            <x-slot:header>{{ __('Column Standard') }}</x-slot:header>
            <p class="crm-muted">{{ implode(', ', $headers) }}</p>
        </x-admin-panel::card>

        <x-admin-panel::card>
            <form method="POST" action="{{ route('crm.'.$module.'.import.preview') }}" enctype="multipart/form-data" class="crm-stack" data-crm-import-form data-crm-import-preview-url="{{ route('crm.'.$module.'.import.preview') }}">
                @csrf
                <x-admin-panel::input name="file" label="CSV or XLSX File" type="file" required />
                <div class="crm-row-actions">
                    <x-admin-panel::button type="submit" icon="eye" data-crm-preview-btn>{{ __('Preview') }}</x-admin-panel::button>
                    <x-admin-panel::button type="submit" formaction="{{ route('crm.'.$module.'.import.store') }}" icon="upload">
                        {{ __('Import') }}
                    </x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        <div class="crm-import-preview" data-crm-import-preview>
            @if(session('crm_import_preview'))
                @include('crm::admin.data-transfer._preview', ['preview' => session('crm_import_preview')])
            @endif
        </div>

        @if(session('crm_import_result'))
            <x-admin-panel::card>
                <x-slot:header>{{ __('Import Report') }}</x-slot:header>
                @php($result = session('crm_import_result'))
                <p>{{ __(':created created, :failed failed.', ['created' => $result['created'], 'failed' => $result['failed']]) }}</p>
                @if(!empty($result['error_report_url']))
                    <x-admin-panel::button :href="$result['error_report_url']" variant="outline" icon="download">
                        {{ __('Download Error Report') }}
                    </x-admin-panel::button>
                @endif
                @if(!empty($result['errors']))
                    <x-admin-panel::table :headers="[__('Row'), __('Error')]">
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
