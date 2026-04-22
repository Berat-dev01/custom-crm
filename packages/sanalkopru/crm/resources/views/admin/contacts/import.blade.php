@extends('admin-panel::layouts.app')

@section('title', 'Import Contacts')
@section('page-title', 'Import Contacts')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="contacts">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Contacts</p>
                <h1>Import Contacts</h1>
            </div>
            <x-admin-panel::button :href="route('crm.contacts.index')" variant="ghost" icon="arrow-left">
                Back
            </x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <form method="POST" action="{{ route('crm.contacts.import.store') }}" enctype="multipart/form-data" class="crm-stack">
                @csrf
                <x-admin-panel::input name="file" label="CSV File" type="file" required />
                <x-admin-panel::button type="submit" icon="upload">Import CSV</x-admin-panel::button>
            </form>
        </x-admin-panel::card>

        @if(session('crm_import_result'))
            <x-admin-panel::card>
                <x-slot:header>Import Report</x-slot:header>
                @php($result = session('crm_import_result'))
                <p>{{ $result['created'] }} created, {{ $result['failed'] }} failed.</p>
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
