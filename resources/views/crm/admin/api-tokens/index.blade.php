@extends('crm::layouts.app')

@section('title', __('API Tokens'))
@section('page-title', __('API Tokens'))


@section('content')
    <section class="crm-admin-page" data-crm-module="api-tokens">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Settings') }}</p>
                <h1>{{ __('API Tokens') }}</h1>
                <p class="crm-muted">{{ __('Issue and revoke bearer tokens for the CRM REST API.') }}</p>
            </div>
        </header>

        @if(session('crm_api_token_plain'))
            <x-admin-panel::card>
                <x-slot:header>{{ __('Copy your new token now') }}</x-slot:header>
                <p class="crm-muted">{{ __('This token is shown only once. Store it somewhere safe.') }}</p>
                <pre class="crm-token-plain"><code>{{ session('crm_api_token_plain') }}</code></pre>
            </x-admin-panel::card>
        @endif

        <x-admin-panel::card>
            <x-slot:header>{{ __('Create token') }}</x-slot:header>

            <form method="POST" action="{{ route('crm.api-tokens.store') }}" class="crm-form-grid">
                @csrf

                <div class="form-group">
                    <label for="token-name">{{ __('Token name') }}</label>
                    <input id="token-name" type="text" name="name" value="{{ old('name') }}" class="form-control" required maxlength="120" placeholder="{{ __('e.g. Zapier integration') }}">
                    @error('name')<div class="crm-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="token-user">{{ __('Acts as user') }}</label>
                    <select id="token-user" name="user_id" class="form-control" required>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="crm-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="token-expires">{{ __('Expires at (optional)') }}</label>
                    <input id="token-expires" type="date" name="expires_at" value="{{ old('expires_at') }}" class="form-control">
                    @error('expires_at')<div class="crm-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="crm-form-actions">
                    <x-admin-panel::button type="submit" icon="key-round">
                        {{ __('Create token') }}
                    </x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        <x-admin-panel::card>
            <x-slot:header>{{ __('Active tokens') }}</x-slot:header>

            <x-admin-panel::table :headers="[
                ['label' => __('Name')],
                ['label' => __('User')],
                ['label' => __('Last used')],
                ['label' => __('Expires')],
                ['label' => __('Created')],
                ['label' => __('Actions'), 'width' => '120px'],
            ]">
                @forelse($tokens as $token)
                    <tr>
                        <td><strong>{{ $token->name }}</strong></td>
                        <td>{{ $token->user?->name ?? '—' }}</td>
                        <td>{{ $token->last_used_at?->diffForHumans() ?? __('Never') }}</td>
                        <td>
                            @if($token->isExpired())
                                <x-admin-panel::badge variant="danger">{{ __('Expired') }}</x-admin-panel::badge>
                            @else
                                {{ $token->expires_at?->format('d.m.Y') ?? __('Never') }}
                            @endif
                        </td>
                        <td>{{ $token->created_at->format('d.m.Y') }}</td>
                        <td>
                            <form method="POST" action="{{ route('crm.api-tokens.destroy', $token) }}" data-admin-confirm="{{ __('Revoke this token? Applications using it will stop working.') }}">
                                @csrf
                                @method('DELETE')
                                <x-admin-panel::button type="submit" variant="outline" icon="trash-2">
                                    {{ __('Revoke') }}
                                </x-admin-panel::button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            @include('crm::admin.partials.empty-state', [
                                'title' => __('No API tokens yet'),
                                'body' => __('Create a token to integrate external tools with the CRM API.'),
                            ])
                        </td>
                    </tr>
                @endforelse
            </x-admin-panel::table>

            <x-admin-panel::pagination :paginator="$tokens" class="crm-pagination" />
        </x-admin-panel::card>
    </section>
@endsection
