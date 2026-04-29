@extends('crm::layouts.app')

@section('title', __('Import Contacts'))
@section('page-title', __('Import Contacts'))


@section('content')
    <section class="crm-admin-page" data-crm-module="contacts">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Contacts') }}</p>
                <h1>{{ __('Import Contacts') }}</h1>
            </div>
            <x-admin-panel::button :href="route('crm.contacts.index')" variant="ghost" icon="arrow-left">
                {{ __('Back') }}
            </x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <form method="POST" action="{{ route('crm.contacts.import.store') }}" enctype="multipart/form-data" class="crm-stack">
                @csrf
                <x-admin-panel::input name="file" label="CSV File" type="file" required />
                <x-admin-panel::button type="submit" icon="upload">{{ __('Import CSV') }}</x-admin-panel::button>
            </form>
        </x-admin-panel::card>

        @if(session('crm_import_result'))
            <x-admin-panel::card>
                <x-slot:header>{{ __('Import Report') }}</x-slot:header>
                @php($result = session('crm_import_result'))
                <p>{{ __(':created created, :failed failed.', ['created' => $result['created'], 'failed' => $result['failed']]) }}</p>
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
