@extends('admin-panel::layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="users">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / System</p>
                <h1>Users</h1>
            </div>
            <div class="crm-admin-actions">
                <x-admin-panel::button :href="route('crm.users.create')" icon="plus">
                    New User
                </x-admin-panel::button>
            </div>
        </header>

        <x-admin-panel::card>
            <x-admin-panel::table :headers="[
                ['label' => 'Name'],
                ['label' => 'Email'],
                ['label' => 'CRM Role'],
                ['label' => 'Status'],
                ['label' => 'Actions', 'width' => '200px'],
            ]">
                @forelse($users as $user)
                    @php
                        $roleKey = collect($crmRoles)->search(fn ($name) => $user->hasRole($name));
                        $roleName = $roleKey ? $crmRoles[$roleKey] : null;
                        $isSelf = $user->id === auth()->id();
                    @endphp
                    <tr class="{{ $user->is_active ? '' : 'crm-row-inactive' }}">
                        <td>
                            <strong>{{ $user->name }}</strong>
                            @if($isSelf)
                                <span class="crm-badge-you">You</span>
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($roleName)
                                <x-admin-panel::badge variant="{{ $roleKey === 'owner' ? 'primary' : 'default' }}">
                                    {{ $roleName }}
                                </x-admin-panel::badge>
                            @else
                                <span class="crm-muted">No CRM role</span>
                            @endif
                        </td>
                        <td>
                            @if($user->is_active)
                                <x-admin-panel::badge variant="success">Active</x-admin-panel::badge>
                            @else
                                <x-admin-panel::badge variant="danger">Inactive</x-admin-panel::badge>
                            @endif
                        </td>
                        <td>
                            <div class="crm-row-actions">
                                <x-admin-panel::button :href="route('crm.users.edit', $user)" size="sm" variant="ghost" icon="pencil" />

                                @if(!$isSelf)
                                    <form method="POST" action="{{ route('crm.users.toggle-active', $user) }}" data-admin-confirm="{{ $user->is_active ? 'Deactivate this user?' : 'Activate this user?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-admin-panel::button type="submit" size="sm" variant="{{ $user->is_active ? 'outline' : 'ghost' }}" icon="{{ $user->is_active ? 'user-x' : 'user-check' }}" />
                                    </form>

                                    <form method="POST" action="{{ route('crm.users.destroy', $user) }}" data-admin-confirm="Permanently delete {{ $user->name }}? This cannot be undone.">
                                        @csrf
                                        @method('DELETE')
                                        <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" />
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            @include('crm::admin.partials.empty-state', [
                                'title' => 'No users found.',
                                'body' => 'Create the first CRM user.',
                                'actionUrl' => route('crm.users.create'),
                                'actionLabel' => 'New User',
                                'actionPermission' => 'crm.users.manage',
                            ])
                        </td>
                    </tr>
                @endforelse
            </x-admin-panel::table>
        </x-admin-panel::card>
    </section>
@endsection
