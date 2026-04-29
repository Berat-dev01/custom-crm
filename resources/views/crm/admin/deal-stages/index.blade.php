@extends('crm::layouts.app')

@section('title', __('Deal Stages'))
@section('page-title', __('Deal Stages'))


@section('content')
    <section class="crm-admin-page" data-crm-module="deal-stages">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Settings') }}</p>
                <h1>{{ __('Deal Stages') }}</h1>
            </div>

            <x-admin-panel::button :href="route('crm.deal-stages.create')" icon="plus">
                {{ __('New Stage') }}
            </x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <x-slot:header>{{ __('Pipeline Order') }}</x-slot:header>

            <form id="reorder-form" method="POST" action="{{ route('crm.deal-stages.reorder') }}" class="crm-stack">
                @csrf

                <x-admin-panel::table :headers="[
                    ['label' => __('Stage')],
                    ['label' => __('Position'), 'width' => '120px'],
                    ['label' => __('Probability'), 'width' => '120px'],
                    ['label' => __('Type'), 'width' => '120px'],
                    ['label' => __('Deals'), 'width' => '90px'],
                    ['label' => __('Actions'), 'width' => '240px'],
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
                                    <span class="crm-stage-kind crm-stage-kind-won">{{ __('Won') }}</span>
                                @elseif($stage->is_lost)
                                    <span class="crm-stage-kind crm-stage-kind-lost">{{ __('Lost') }}</span>
                                @else
                                    <span class="crm-stage-kind crm-stage-kind-open">{{ __('Open') }}</span>
                                @endif
                            </td>
                            <td>{{ $stage->deals_count }}</td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('crm.deal-stages.edit', $stage)" size="sm" variant="ghost" icon="pencil" />

                                    <form method="POST" action="{{ route('crm.deal-stages.destroy', $stage) }}" class="crm-inline-form" data-crm-confirm="{{ __('Delete this deal stage?') }}">
                                        @csrf
                                        @method('DELETE')
                                        @if($stage->deals_count > 0)
                                            <select
                                                name="replacement_stage_id"
                                                class="form-control"
                                                aria-label="{{ __('Replacement stage') }}"
                                            >
                                                <option value="">{{ __('Move to...') }}</option>
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
                            <td colspan="6" class="crm-empty">{{ __('No deal stages found.') }}</td>
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
                    <x-admin-panel::button type="submit" form="reorder-form" icon="save">{{ __('Save Order') }}</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>
    </section>
@endsection
