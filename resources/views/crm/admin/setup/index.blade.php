@extends('crm::layouts.app')

@section('title', __('Setup'))
@section('page-title', __('Setup'))


@section('content')
    <section class="crm-admin-page" data-crm-module="setup">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / System') }}</p>
                <h1>{{ __('Setup') }}</h1>
                <p class="crm-muted">{{ __('Get the CRM ready for your team in a few steps.') }}</p>
            </div>
            <x-admin-panel::badge :variant="$completed === $total ? 'success' : 'primary'">
                {{ __(':done of :total completed', ['done' => $completed, 'total' => $total]) }}
            </x-admin-panel::badge>
        </header>

        @if($completed === $total)
            <x-admin-panel::card>
                <strong>{{ __('All set!') }}</strong>
                <p class="crm-muted">{{ __('Your CRM is fully configured. You can revisit any step below at any time.') }}</p>
            </x-admin-panel::card>
        @endif

        @foreach($steps as $step)
            <x-admin-panel::card>
                <div class="crm-setup-step{{ $step['done'] ? ' is-done' : '' }}">
                    <div class="crm-setup-step-status" aria-hidden="true">
                        @if($step['done'])
                            <i data-lucide="check-circle-2" width="22" height="22"></i>
                        @else
                            <i data-lucide="circle" width="22" height="22"></i>
                        @endif
                    </div>
                    <div class="crm-setup-step-body">
                        <strong>{{ $step['title'] }}</strong>
                        <span class="crm-sr-only">{{ $step['done'] ? __('Completed') : __('Pending') }}</span>
                        <p class="crm-muted">{{ $step['description'] }}</p>
                    </div>
                    <div class="crm-setup-step-action">
                        <x-admin-panel::button :href="$step['url']" :variant="$step['done'] ? 'ghost' : 'outline'">
                            {{ $step['action'] }}
                        </x-admin-panel::button>
                    </div>
                </div>
            </x-admin-panel::card>
        @endforeach
    </section>
@endsection
