<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ admin_trans('Admin Login') }} - {{ config('app.name') }}</title>

    <!-- Bootstrap 5 Grid & Utilities -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap-grid.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap-utilities.min.css" rel="stylesheet">

    @php
        $adminAssetPath = config('admin-panel.asset_path', 'vendor/admin-panel');
        $adminCssUrl = asset($adminAssetPath . '/css/admin.css') . '?v=' . (@filemtime(public_path($adminAssetPath . '/css/admin.css')) ?: time());
        $adminJsUrl = asset($adminAssetPath . '/js/admin.js') . '?v=' . (@filemtime(public_path($adminAssetPath . '/js/admin.js')) ?: time());
    @endphp

    <!-- Admin Panel CSS -->
    <link rel="stylesheet" href="{{ $adminCssUrl }}">

    <style>
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-4);
        }

        .login-card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 420px;
            width: 100%;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            padding: var(--space-8);
            text-align: center;
        }

        .login-header h1 {
            color: white;
            font-size: var(--text-2xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--space-2);
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
        }

        .login-body {
            padding: var(--space-8);
        }

        .login-footer {
            padding: var(--space-4) var(--space-8);
            background-color: var(--bg-secondary);
            text-align: center;
            font-size: var(--text-sm);
            color: var(--text-secondary);
            border-top: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1>{{ admin_trans('Admin Panel') }}</h1>
            <p>{{ config('business.company_name') }}</p>
        </div>

        <div class="login-body">
            @if ($errors->any())
                <x-admin-panel::alert variant="danger" class="mb-4">
                    <ul class="m-0 p-0" style="list-style: none;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-admin-panel::alert>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf

                <x-admin-panel::input
                    type="email"
                    name="email"
                    label="Email Address"
                    :value="old('email')"
                    placeholder="Enter your email address"
                    icon="mail"
                    required
                    autofocus
                />

                <x-admin-panel::input
                    type="password"
                    name="password"
                    label="Password"
                    placeholder="Enter your password"
                    icon="lock"
                    required
                />

                <x-admin-panel::checkbox
                    name="remember"
                    label="Remember me"
                />

                <x-admin-panel::button type="submit" variant="primary" class="w-full mt-4">
                    {{ admin_trans('Login to Dashboard') }}
                </x-admin-panel::button>
            </form>
        </div>

        <div class="login-footer">
            &copy; {{ date('Y') }} {{ config('business.company_name') }}. {{ admin_trans('All rights reserved.') }}
        </div>
    </div>

    @include('admin-panel::partials.translations')
    <script src="{{ $adminJsUrl }}"></script>
</body>
</html>
