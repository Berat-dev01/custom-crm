@extends('crm::layouts.app')

@section('title', __('Webhooks'))
@section('page-title', __('Webhooks'))


@section('content')
    <section class="crm-admin-page" data-crm-module="webhooks">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / System') }}</p>
                <h1>{{ __('Webhooks') }}</h1>
                <p class="crm-muted">{{ __('Push CRM events to external systems as signed HTTP requests.') }}</p>
            </div>
        </header>

        @if(session('crm_webhook_secret'))
            <x-admin-panel::card>
                <x-slot:header>{{ __('Copy your signing secret now') }}</x-slot:header>
                <p class="crm-muted">{{ __('Use it to verify the X-CRM-Signature header. It is shown only once.') }}</p>
                <pre class="crm-token-plain"><code>{{ session('crm_webhook_secret') }}</code></pre>
            </x-admin-panel::card>
        @endif

        <x-admin-panel::card>
            <x-slot:header>{{ __('Create webhook') }}</x-slot:header>

            <form method="POST" action="{{ route('crm.webhooks.store') }}" class="crm-form-grid">
                @csrf

                <div class="form-group">
                    <label for="webhook-name">{{ __('Name') }}</label>
                    <input id="webhook-name" type="text" name="name" value="{{ old('name') }}" class="form-control" required maxlength="120">
                    @error('name')<div class="crm-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="webhook-url">{{ __('Payload URL') }}</label>
                    <input id="webhook-url" type="url" name="url" value="{{ old('url') }}" class="form-control" required maxlength="500" placeholder="https://example.com/crm-webhook">
                    @error('url')<div class="crm-field-error">{{ $message }}</div>@enderror
                </div>

                <fieldset class="form-group">
                    <legend>{{ __('Events') }}</legend>
                    <div class="crm-settings-toggles">
                        @foreach($availableEvents as $event)
                            <label class="crm-checkbox-row">
                                <input type="checkbox" name="events[]" value="{{ $event }}" @checked(in_array($event, old('events', []), true))>
                                <span><code>{{ $event }}</code></span>
                            </label>
                        @endforeach
                    </div>
                    @error('events')<div class="crm-field-error">{{ $message }}</div>@enderror
                </fieldset>

                <div class="crm-form-actions">
                    <x-admin-panel::button type="submit" icon="webhook">
                        {{ __('Create webhook') }}
                    </x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        <x-admin-panel::card>
            <x-slot:header>{{ __('Configured webhooks') }}</x-slot:header>

            <x-admin-panel::table :headers="[
                ['label' => __('Name')],
                ['label' => __('URL')],
                ['label' => __('Events')],
                ['label' => __('Status')],
                ['label' => __('Deliveries')],
                ['label' => __('Actions'), 'width' => '220px'],
            ]">
                @forelse($webhooks as $webhook)
                    <tr>
                        <td><strong>{{ $webhook->name }}</strong></td>
                        <td class="crm-truncate">{{ $webhook->url }}</td>
                        <td>
                            @foreach($webhook->events as $event)
                                <code>{{ $event }}</code>@if(! $loop->last), @endif
                            @endforeach
                        </td>
                        <td>
                            <x-admin-panel::badge :variant="$webhook->is_active ? 'success' : 'secondary'">
                                {{ $webhook->is_active ? __('Active') : __('Paused') }}
                            </x-admin-panel::badge>
                        </td>
                        <td>{{ $webhook->deliveries_count }}</td>
                        <td>
                            <div class="crm-admin-actions">
                                <form method="POST" action="{{ route('crm.webhooks.toggle', $webhook) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-admin-panel::button type="submit" variant="outline" icon="{{ $webhook->is_active ? 'pause' : 'play' }}">
                                        {{ $webhook->is_active ? __('Pause') : __('Resume') }}
                                    </x-admin-panel::button>
                                </form>
                                <form method="POST" action="{{ route('crm.webhooks.destroy', $webhook) }}" data-admin-confirm="{{ __('Delete this webhook? Deliveries will stop immediately.') }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-admin-panel::button type="submit" variant="outline" icon="trash-2">
                                        {{ __('Delete') }}
                                    </x-admin-panel::button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            @include('crm::admin.partials.empty-state', [
                                'title' => __('No webhooks yet'),
                                'body' => __('Create a webhook to notify external tools about CRM events.'),
                            ])
                        </td>
                    </tr>
                @endforelse
            </x-admin-panel::table>

            <x-admin-panel::pagination :paginator="$webhooks" class="crm-pagination" />
        </x-admin-panel::card>

        <x-admin-panel::card>
            <x-slot:header>{{ __('Recent deliveries') }}</x-slot:header>

            <x-admin-panel::table :headers="[
                ['label' => __('When')],
                ['label' => __('Webhook')],
                ['label' => __('Event')],
                ['label' => __('Status')],
                ['label' => __('HTTP')],
                ['label' => __('Attempts')],
            ]">
                @forelse($deliveries as $delivery)
                    <tr>
                        <td>{{ $delivery->created_at->format('d.m.Y H:i') }}</td>
                        <td>{{ $delivery->webhook?->name ?? '—' }}</td>
                        <td><code>{{ $delivery->event }}</code></td>
                        <td>
                            <x-admin-panel::badge :variant="match($delivery->status) {
                                'success' => 'success',
                                'failed' => 'danger',
                                default => 'secondary',
                            }">
                                {{ $delivery->status }}
                            </x-admin-panel::badge>
                        </td>
                        <td>{{ $delivery->response_status ?? '—' }}</td>
                        <td>{{ $delivery->attempts }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            @include('crm::admin.partials.empty-state', [
                                'title' => __('No deliveries yet'),
                                'body' => __('Deliveries appear here once a subscribed event fires.'),
                            ])
                        </td>
                    </tr>
                @endforelse
            </x-admin-panel::table>
        </x-admin-panel::card>
    </section>
@endsection
