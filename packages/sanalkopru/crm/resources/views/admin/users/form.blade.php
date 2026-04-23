@extends('admin-panel::layouts.app')

@section('title', $user->exists ? 'Edit '.$user->name : 'New User')
@section('page-title', $user->exists ? 'Edit '.$user->name : 'New User')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="users">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / System / Users</p>
                <h1>{{ $user->exists ? 'Edit '.$user->name : 'New User' }}</h1>
            </div>
            <div class="crm-admin-actions">
                <x-admin-panel::button :href="route('crm.users.index')" variant="ghost" icon="arrow-left">
                    Back
                </x-admin-panel::button>
            </div>
        </header>

        <x-admin-panel::card>
            <x-slot:header>User Details</x-slot:header>

            <form method="POST"
                  action="{{ $user->exists ? route('crm.users.update', $user) : route('crm.users.store') }}"
                  class="crm-form-grid">
                @csrf
                @if($user->exists)
                    @method('PUT')
                @endif

                <x-admin-panel::input
                    name="name"
                    label="Name"
                    :value="old('name', $user->name)"
                    required
                    class="crm-span-2"
                />

                <x-admin-panel::input
                    name="email"
                    label="Email"
                    type="email"
                    :value="old('email', $user->email)"
                    required
                    class="crm-span-2"
                />

                <x-admin-panel::select
                    name="crm_role"
                    label="CRM Role"
                    :options="$crmRoles"
                    :selected="old('crm_role', $currentRole ?? null)"
                    placeholder="No CRM role"
                    class="crm-span-2"
                />

                <x-admin-panel::input
                    name="password"
                    label="{{ $user->exists ? 'New Password (leave blank to keep current)' : 'Password' }}"
                    type="password"
                    :required="!$user->exists"
                />

                <x-admin-panel::input
                    name="password_confirmation"
                    label="Confirm Password"
                    type="password"
                    :required="!$user->exists"
                />

                <div class="crm-form-actions crm-span-2">
                    <x-admin-panel::button type="submit" icon="save">
                        {{ $user->exists ? 'Update User' : 'Create User' }}
                    </x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        @if($user->exists)
            <x-admin-panel::card>
                <x-slot:header>Danger Zone</x-slot:header>

                <div class="crm-row-actions">
                    @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('crm.users.toggle-active', $user) }}" data-admin-confirm="{{ $user->is_active ? 'Deactivate this user?' : 'Activate this user?' }}">
                            @csrf
                            @method('PATCH')
                            <x-admin-panel::button type="submit" variant="{{ $user->is_active ? 'outline' : 'ghost' }}" icon="{{ $user->is_active ? 'user-x' : 'user-check' }}">
                                {{ $user->is_active ? 'Deactivate User' : 'Activate User' }}
                            </x-admin-panel::button>
                        </form>

                        <form method="POST" action="{{ route('crm.users.destroy', $user) }}" data-admin-confirm="Permanently delete {{ $user->name }}? This cannot be undone.">
                            @csrf
                            @method('DELETE')
                            <x-admin-panel::button type="submit" variant="danger" icon="trash-2">
                                Delete User
                            </x-admin-panel::button>
                        </form>
                    @else
                        <p class="crm-muted">You cannot deactivate or delete your own account.</p>
                    @endif
                </div>
            </x-admin-panel::card>
        @endif
    </section>
@endsection
