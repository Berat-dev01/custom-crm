@extends('crm::layouts.app')

@section('title', __('Notifications'))
@section('page-title', __('Notifications'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="notifications">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header">
            <div class="crm-admin-header-row">
                <div>
                    <p class="crm-admin-eyebrow">{{ __('CRM / Notifications') }}</p>
                    <h1>{{ __('Notifications') }}</h1>
                    <p class="crm-muted">{{ __('Recent CRM updates, assignments, quote changes and import results.') }}</p>
                </div>
                <div class="crm-admin-actions">
                    <x-admin-panel::badge :variant="$unreadCount > 0 ? 'primary' : 'secondary'">
                        {{ $unreadCount > 0 ? __(':count unread', ['count' => $unreadCount]) : __('All caught up') }}
                    </x-admin-panel::badge>
                    @if($unreadCount > 0)
                        <form method="POST" action="{{ route('crm.notifications.read-all') }}">
                            @csrf
                            <x-admin-panel::button type="submit" variant="outline" icon="check-check">
                                {{ __('Mark all as read') }}
                            </x-admin-panel::button>
                        </form>
                    @endif
                </div>
            </div>
        </header>

        <x-admin-panel::card>
            <x-slot:header>{{ __('Latest notifications') }}</x-slot:header>

            @if($notifications->count() > 0)
                <div class="crm-notification-feed">
                    @foreach($notifications as $notification)
                        <article class="crm-notification-card{{ $notification['unread'] ? ' is-unread' : '' }}">
                            <div class="crm-notification-card-main">
                                <div class="crm-notification-card-title">
                                    <div class="crm-notification-card-heading">
                                        <span class="crm-notification-card-dot"></span>
                                        <strong>{{ $notification['title'] }}</strong>
                                    </div>
                                    <span>{{ $notification['relative_time'] ?: __('Now') }}</span>
                                </div>
                                <p>{{ $notification['body'] }}</p>
                            </div>
                            <div class="crm-notification-card-actions">
                                @if($notification['url'])
                                    <x-admin-panel::button :href="$notification['url']" variant="ghost" icon="arrow-up-right">
                                        {{ __('Open') }}
                                    </x-admin-panel::button>
                                @endif
                                @if($notification['unread'])
                                    <form method="POST" action="{{ route('crm.notifications.read', ['notification' => $notification['id']]) }}">
                                        @csrf
                                        <x-admin-panel::button type="submit" variant="outline" icon="check">
                                            {{ __('Mark read') }}
                                        </x-admin-panel::button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="crm-notification-footer">
                    <x-admin-panel::pagination :paginator="$notifications" />
                </div>
            @else
                <div class="crm-notification-empty">
                    <i data-lucide="bell-off" width="24" height="24"></i>
                    <strong>{{ __('No notifications yet') }}</strong>
                    <p>{{ __('Assignments, quote updates and import results will appear here.') }}</p>
                </div>
            @endif
        </x-admin-panel::card>
    </section>
@endsection
