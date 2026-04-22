@extends('admin-panel::layouts.app')

@section('title', 'Deal Stages')
@section('page-title', 'Deal Stages')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="deal-stages">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Settings</p>
                <h1>Deal Stages</h1>
            </div>

            <x-admin-panel::button :href="route('crm.deal-stages.create')" icon="plus">
                New Stage
            </x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <x-slot:header>
                Pipeline Order
            </x-slot:header>

            <form method="POST" action="{{ route('crm.deal-stages.reorder') }}" class="crm-stack">
                @csrf

                <x-admin-panel::table :headers="[
                    ['label' => 'Stage'],
                    ['label' => 'Position', 'width' => '120px'],
                    ['label' => 'Probability', 'width' => '120px'],
                    ['label' => 'Type', 'width' => '120px'],
                    ['label' => 'Deals', 'width' => '90px'],
                    ['label' => 'Actions', 'width' => '240px'],
                ]">
                    @forelse($stages as $stage)
                        <tr>
                            <td>
                                <span class="crm-color-swatch" style="background: {{ $stage->color }}"></span>
                                <strong>{{ $stage->name }}</strong>
                                <div class="crm-muted">{{ $stage->slug }}</div>
                            </td>
                            <td>
                                <input
                                    type="hidden"
                                    name="stages[{{ $loop->index }}][id]"
                                    value="{{ $stage->id }}"
                                >
                                <input
                                    type="number"
                                    name="stages[{{ $loop->index }}][position]"
                                    value="{{ old('stages.'.$loop->index.'.position', $stage->position) }}"
                                    min="1"
                                    max="1000"
                                    class="form-control crm-compact-input"
                                >
                            </td>
                            <td>{{ $stage->probability }}%</td>
                            <td>
                                @if($stage->is_won)
                                    <span class="crm-stage-kind crm-stage-kind-won">Won</span>
                                @elseif($stage->is_lost)
                                    <span class="crm-stage-kind crm-stage-kind-lost">Lost</span>
                                @else
                                    <span class="crm-stage-kind crm-stage-kind-open">Open</span>
                                @endif
                            </td>
                            <td>{{ $stage->deals_count }}</td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('crm.deal-stages.edit', $stage)" size="sm" variant="ghost" icon="pencil" />

                                    <form method="POST" action="{{ route('crm.deal-stages.destroy', $stage) }}" class="crm-inline-form">
                                        @csrf
                                        @method('DELETE')
                                        @if($stage->deals_count > 0)
                                            <select
                                                name="replacement_stage_id"
                                                class="form-control"
                                                aria-label="Replacement stage"
                                            >
                                                <option value="">Move to...</option>
                                                @foreach($stages as $replacementStage)
                                                    @continue($replacementStage->is($stage))
                                                    <option value="{{ $replacementStage->id }}">
                                                        {{ $replacementStage->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                        <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="crm-empty">No deal stages found.</td>
                        </tr>
                    @endforelse
                </x-admin-panel::table>

                @error('replacement_stage_id')
                    <small class="form-error">{{ $message }}</small>
                @enderror
                @error('stages')
                    <small class="form-error">{{ $message }}</small>
                @enderror

                <div class="crm-form-actions">
                    <x-admin-panel::button type="submit" icon="save">Save Order</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>
    </section>
@endsection
