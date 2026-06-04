<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ admin_trans('Two-Factor Authentication') }} - {{ config('admin-panel.app_name', 'Admin Panel') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap-grid.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap-utilities.min.css" rel="stylesheet">
    @php
        $adminAssetPath = config('admin-panel.asset_path', 'vendor/admin-panel');
        $adminCssUrl = asset($adminAssetPath . '/css/admin.css') . '?v=' . (@filemtime(public_path($adminAssetPath . '/css/admin.css')) ?: time());
        $adminJsUrl = asset($adminAssetPath . '/js/admin.js') . '?v=' . (@filemtime(public_path($adminAssetPath . '/js/admin.js')) ?: time());
    @endphp
    <link rel="stylesheet" href="{{ $adminCssUrl }}">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh; background: var(--bg-secondary);">
    <div class="card" style="width: 100%; max-width: 420px;">
        <div class="card-header">
            <h1 class="card-title">{{ admin_trans('Two-Factor Authentication') }}</h1>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.2fa.verify') }}">
                @csrf

                <x-admin-panel::input
                    name="code"
                    label="Authentication Code"
                    placeholder="Enter your authentication code"
                    autocomplete="one-time-code"
                    required
                    autofocus
                />

                <x-admin-panel::button type="submit" variant="primary" class="w-full mt-4">
                    {{ admin_trans('Verify') }}
                </x-admin-panel::button>
            </form>
        </div>
    </div>

    @include('admin-panel::partials.translations')
    <script src="{{ $adminJsUrl }}"></script>
</body>
</html>
