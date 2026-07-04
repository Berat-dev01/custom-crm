<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Two-factor authentication') }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; background: #f3f4f6; color: #111827; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .tfa-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); padding: 32px; width: 100%; max-width: 380px; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { color: #6b7280; font-size: 14px; margin: 0 0 20px; }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; }
        input { width: 100%; box-sizing: border-box; border: 1px solid #d1d5db; border-radius: 8px; padding: 12px; font-size: 18px; letter-spacing: .2em; text-align: center; }
        button { width: 100%; margin-top: 16px; padding: 12px; border: 0; border-radius: 8px; background: #111827; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; }
        .error { color: #b91c1c; font-size: 13px; margin-top: 8px; }
        .hint { margin-top: 16px; font-size: 13px; color: #6b7280; }
    </style>
</head>
<body>
<div class="tfa-card">
    <h1>{{ __('Two-factor authentication') }}</h1>
    <p>{{ __('Enter the 6-digit code from your authenticator app, or one of your recovery codes.') }}</p>

    <form method="POST" action="{{ route('admin.login.2fa.post') }}">
        @csrf
        <label for="code">{{ __('Authentication code') }}</label>
        <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="20" autofocus required>
        @error('code')<div class="error">{{ $message }}</div>@enderror
        <button type="submit">{{ __('Verify') }}</button>
    </form>

    <div class="hint">{{ __('Lost your device? Use a recovery code in the same field.') }}</div>
</div>
</body>
</html>
