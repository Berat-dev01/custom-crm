@extends('crm::layouts.app')

@section('title', __('Security'))
@section('page-title', __('Security'))


@section('content')
    <section class="crm-admin-page" data-crm-module="security">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Account') }}</p>
                <h1>{{ __('Security') }}</h1>
                <p class="crm-muted">{{ __('Protect your account with two-factor authentication (TOTP).') }}</p>
            </div>
        </header>

        @if($recoveryCodes)
            <x-admin-panel::card>
                <x-slot:header>{{ __('Recovery codes') }}</x-slot:header>
                <p class="crm-muted">{{ __('Store these codes somewhere safe. Each code can be used once if you lose access to your authenticator app. They are shown only now.') }}</p>
                <pre class="crm-token-plain"><code>@foreach($recoveryCodes as $code){{ $code }}
@endforeach</code></pre>
            </x-admin-panel::card>
        @endif

        <x-admin-panel::card>
            <x-slot:header>{{ __('Two-factor authentication') }}</x-slot:header>

            @if($user->hasTwoFactorEnabled())
                <p>
                    <x-admin-panel::badge variant="success">{{ __('Enabled') }}</x-admin-panel::badge>
                    <span class="crm-muted">{{ __('since :date', ['date' => $user->two_factor_confirmed_at->format('d.m.Y H:i')]) }}</span>
                </p>

                <form method="POST" action="{{ route('crm.security.2fa.disable') }}" class="crm-form-grid">
                    @csrf
                    @method('DELETE')

                    <div class="form-group">
                        <label for="disable-password">{{ __('Confirm your password to disable') }}</label>
                        <input id="disable-password" type="password" name="password" class="form-control" required autocomplete="current-password">
                        @error('password')<div class="crm-field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="crm-form-actions">
                        <x-admin-panel::button type="submit" variant="outline" icon="shield-off">
                            {{ __('Disable two-factor') }}
                        </x-admin-panel::button>
                    </div>
                </form>
            @elseif($pendingSecret)
                <p class="crm-muted">{{ __('Scan the QR code with Google Authenticator, 1Password or a compatible app, then enter the 6-digit code to confirm.') }}</p>

                <div class="crm-2fa-setup">
                    <div class="crm-2fa-qr">{!! $qrSvg !!}</div>
                    <p class="crm-muted">{{ __('Manual entry key') }}: <code>{{ $pendingSecret }}</code></p>
                </div>

                <form method="POST" action="{{ route('crm.security.2fa.confirm') }}" class="crm-form-grid">
                    @csrf

                    <div class="form-group">
                        <label for="confirm-code">{{ __('Authentication code') }}</label>
                        <input id="confirm-code" type="text" name="code" class="form-control" required inputmode="numeric" autocomplete="one-time-code" maxlength="10" placeholder="123 456">
                        @error('code')<div class="crm-field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="crm-form-actions">
                        <x-admin-panel::button type="submit" icon="shield-check">
                            {{ __('Confirm and enable') }}
                        </x-admin-panel::button>
                    </div>
                </form>
            @else
                <p>
                    <x-admin-panel::badge variant="secondary">{{ __('Disabled') }}</x-admin-panel::badge>
                </p>
                <p class="crm-muted">{{ __('When enabled, signing in requires a 6-digit code from your authenticator app in addition to your password.') }}</p>

                <form method="POST" action="{{ route('crm.security.2fa.enable') }}">
                    @csrf
                    <x-admin-panel::button type="submit" icon="shield">
                        {{ __('Enable two-factor') }}
                    </x-admin-panel::button>
                </form>
            @endif
        </x-admin-panel::card>
    </section>
@endsection
