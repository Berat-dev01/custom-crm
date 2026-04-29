@extends('crm::layouts.app')

@section('title', $deal->title)
@section('page-title', $deal->title)


@section('content')
    <section class="crm-admin-page" data-crm-module="deals">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Deals') }}</p>
                <h1>{{ $deal->title }}</h1>
            </div>

            <div class="crm-admin-actions">
                @can('update', $deal)
                    <x-admin-panel::button :href="route('crm.deals.edit', $deal)" icon="pencil">
                        Edit
                    </x-admin-panel::button>
                @endcan
                @can('delete', $deal)
                    <form method="POST" action="{{ route('crm.deals.destroy', $deal) }}" data-crm-confirm="{{ __('Delete this deal?') }}">
                        @csrf
                        @method('DELETE')
                        <x-admin-panel::button type="submit" variant="danger" icon="trash-2">
                            Delete
                        </x-admin-panel::button>
                    </form>
                @endcan
                <x-admin-panel::button :href="route('crm.deals.index')" variant="ghost" icon="arrow-left">
                    Back
                </x-admin-panel::button>
            </div>
        </header>

        <div class="crm-admin-grid">
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">{{ __('Value') }}</span>
                <strong>{{ $deal->currency }} {{ number_format((float) $deal->value, 2) }}</strong>
                <p>{{ __(':value% probability', ['value' => $deal->probability]) }}</p>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">{{ __('Weighted Value') }}</span>
                <strong>{{ $deal->currency }} {{ number_format($weightedValue, 2) }}</strong>
                <p>{{ $crmFormat->status($deal->status) }}</p>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">{{ __('Stage') }}</span>
                <strong>{{ $deal->stage?->name ?: '-' }}</strong>
                <p>{{ $deal->expected_close_date?->format('Y-m-d') ?: __('No expected close date') }}</p>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">{{ __('Next Task') }}</span>
                <strong>{{ $nextTask?->title ?: __('No open task') }}</strong>
                <p>{{ $nextTask?->due_at?->format('Y-m-d H:i') ?: __('Nothing scheduled') }}</p>
            </div>
        </div>

        <div class="crm-highlight-box" data-crm-ai-result hidden>
            <strong data-crm-ai-label>{{ __('AI Result') }}</strong>
            <pre class="crm-muted" style="white-space: pre-wrap; margin: 0;" data-crm-ai-content></pre>
        </div>

        @if(session('crm_ai_draft'))
            <div class="crm-highlight-box">
                <strong>{{ __('AI Email Draft') }}</strong>
                <pre class="crm-muted" style="white-space: pre-wrap; margin: 0;">{{ session('crm_ai_draft') }}</pre>
            </div>
        @endif

        @if(session('crm_ai_summary'))
            <div class="crm-highlight-box">
                <strong>{{ __('AI Summary') }}</strong>
                <pre class="crm-muted" style="white-space: pre-wrap; margin: 0;">{{ session('crm_ai_summary') }}</pre>
            </div>
        @endif

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Deal Summary
                </x-slot:header>

                <dl class="crm-detail-list">
                    <dt>{{ __('Company') }}</dt>
                    <dd>{{ $deal->company?->name ?: '-' }}</dd>
                    <dt>{{ __('Contact') }}</dt>
                    <dd>{{ $deal->contact?->full_name ?: '-' }}</dd>
                    <dt>{{ __('Owner') }}</dt>
                    <dd>{{ $deal->owner?->name ?: '-' }}</dd>
                    <dt>{{ __('Closed At') }}</dt>
                    <dd>{{ $deal->closed_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                    <dt>{{ __('Lost Reason') }}</dt>
                    <dd>{{ $deal->lost_reason ?: '-' }}</dd>
                    <dt>{{ __('Tags') }}</dt>
                    <dd>{{ $deal->tags->pluck('name')->implode(', ') ?: '-' }}</dd>
                </dl>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>
                    Stage & Close
                </x-slot:header>

                <div class="crm-stack">
                    @can('move', $deal)
                        <form method="POST" action="{{ route('crm.deals.stage', $deal) }}" class="crm-action-panel" data-crm-ajax-form>
                            @csrf
                            @method('PATCH')
                            <x-admin-panel::select name="stage_id" label="Stage" :options="$stages" :selected="$deal->stage_id" required />
                            <x-admin-panel::input name="lost_reason" label="Lost Reason" :value="$deal->lost_reason" />
                            <x-admin-panel::button type="submit" icon="move-right">Change Stage</x-admin-panel::button>
                        </form>
                    @endcan

                    @can('close', $deal)
                        <div class="crm-action-panel">
                            <h3>{{ __('Close Deal') }}</h3>
                            <div class="crm-row-actions">
                                <form method="POST" action="{{ route('crm.deals.close-won', $deal) }}" data-crm-ajax-form>
                                    @csrf
                                    @method('PATCH')
                                    <x-admin-panel::button type="submit" variant="success" icon="check">
                                        Mark Won
                                    </x-admin-panel::button>
                                </form>
                                <form method="POST" action="{{ route('crm.deals.close-lost', $deal) }}" class="crm-inline-form" data-crm-ajax-form>
                                    @csrf
                                    @method('PATCH')
                                    <input name="lost_reason" class="form-control" placeholder="{{ __('Lost Reason') }}" required>
                                    <x-admin-panel::button type="submit" variant="danger" icon="x">
                                        Mark Lost
                                    </x-admin-panel::button>
                                </form>
                            </div>
                        </div>
                    @endcan

                    @can('crm.ai.use')
                        <form method="POST" action="{{ route('crm.ai.draft-email') }}" class="crm-action-panel" data-crm-ajax-form data-crm-ai-label="{{ __('AI Email Draft') }}">
                            @csrf
                            <input type="hidden" name="deal_id" value="{{ $deal->id }}">
                            <input type="hidden" name="deal_title" value="{{ $deal->title }}">
                            <input type="hidden" name="brief" value="{{ __('Draft a follow-up email for this deal.') }}">
                            <x-admin-panel::button type="submit" variant="outline" icon="sparkles" :disabled="!$aiAvailable" :title="$aiAvailable ? __('Draft with AI') : trans('crm::messages.ai.not_configured')">
                                {{ __('AI Email Draft') }}
                            </x-admin-panel::button>
                        </form>

                        <form method="POST" action="{{ route('crm.ai.summarize') }}" class="crm-action-panel" data-crm-ajax-form data-crm-ai-label="{{ $deal->status === 'lost' ? __('AI Lost Deal Analysis') : __('AI Timeline Summary') }}">
                            @csrf
                            <input type="hidden" name="type" value="{{ $deal->status === 'lost' ? 'lost_deal' : 'deal_timeline' }}">
                            <input type="hidden" name="deal_id" value="{{ $deal->id }}">
                            <x-admin-panel::button type="submit" variant="ghost" icon="sparkles" :disabled="!$aiAvailable" :title="$aiAvailable ? __('Summarize with AI') : trans('crm::messages.ai.not_configured')">
                                {{ $deal->status === 'lost' ? __('AI Lost Deal Analysis') : __('AI Timeline Summary') }}
                            </x-admin-panel::button>
                        </form>
                    @endcan
                </div>
            </x-admin-panel::card>
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Add Task
                </x-slot:header>

                @can('crm.tasks.create')
                    <form method="POST" action="{{ route('crm.deals.tasks.store', $deal) }}" class="crm-form-grid" data-crm-ajax-form data-crm-reload-region="crm-deal-tasks-list" data-crm-reset-on-success>
                        @csrf
                        <x-admin-panel::input name="title" label="Title" required />
                        <x-admin-panel::select name="priority" label="Priority" :options="$taskPriorities" selected="normal" required />
                        <x-admin-panel::select name="assigned_to" label="Assignee" :options="$owners" :selected="$deal->owner_id" placeholder="{{ __('Unassigned') }}" />
                        <x-admin-panel::input name="due_at" label="Due At" type="datetime-local" />
                        <x-admin-panel::input name="reminder_at" label="Reminder At" type="datetime-local" />
                        <x-admin-panel::textarea name="description" label="Description" class="crm-span-2" rows="3" />
                        <div class="crm-form-actions crm-span-2">
                            <x-admin-panel::button type="submit" icon="check-square">Add Task</x-admin-panel::button>
                        </div>
                    </form>
                @endcan
            </x-admin-panel::card>

            <x-admin-panel::card id="crm-deal-tasks-list">
                <x-slot:header>
                    Open Tasks
                </x-slot:header>

                <div class="crm-stack">
                    @forelse($openTasks as $task)
                        <div class="crm-list-item">
                            <strong>{{ $task->title }}</strong>
                            <span>{{ $task->due_at?->format('Y-m-d H:i') ?: __('No due date') }} / {{ $task->assignee?->name ?: __('Unassigned') }} / {{ $crmFormat->status($task->priority) }}</span>
                        </div>
                    @empty
                        <p class="crm-muted">{{ __('No open tasks.') }}</p>
                    @endforelse
                </div>
            </x-admin-panel::card>
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Create Quote
                </x-slot:header>

                @can('crm.quotes.create')
                    <form method="POST" action="{{ route('crm.deals.quotes.store', $deal) }}" class="crm-form-grid">
                        @csrf
                        <x-admin-panel::input name="item_name" label="Item" :value="$deal->title" required />
                        <x-admin-panel::input name="quantity" label="Quantity" type="number" min="0.001" step="0.001" value="1" required />
                        <x-admin-panel::input name="unit_price" label="Unit Price" type="number" min="0" step="0.01" :value="$deal->value" required />
                        <x-admin-panel::input name="tax_rate" label="Tax Rate" type="number" min="0" max="100" step="0.01" :value="$defaultTaxRate" />
                        <x-admin-panel::select name="currency" label="Currency" :options="$currencies" :selected="$deal->currency" required />
                        <x-admin-panel::input name="valid_until" label="Valid Until" type="date" />
                        <x-admin-panel::textarea name="item_description" label="Item Description" class="crm-span-2" rows="2" />
                        <x-admin-panel::textarea name="notes" label="Notes" class="crm-span-2" rows="2" />
                        <x-admin-panel::textarea name="terms" label="Terms" class="crm-span-2" :value="$defaultTerms" rows="2" />
                        <div class="crm-form-actions crm-span-2">
                                <x-admin-panel::button type="submit" icon="file-plus">Create Quote</x-admin-panel::button>
                            </div>
                        </form>
                @endcan
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>
                    Quotes
                </x-slot:header>

                <div class="crm-stack">
                    @forelse($deal->quotes as $quote)
                        <div class="crm-list-item">
                            <strong><a href="{{ route('crm.quotes.show', $quote) }}">{{ $quote->quote_number }}</a></strong>
                            <span>{{ $crmFormat->status($quote->status) }} / {{ $quote->currency }} {{ number_format((float) $quote->grand_total, 2) }}</span>
                        </div>
                    @empty
                        <p class="crm-muted">{{ __('No quotes yet.') }}</p>
                    @endforelse
                </div>
            </x-admin-panel::card>
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Add Activity
                </x-slot:header>

                @can('crm.activities.create')
                    <form method="POST" action="{{ route('crm.deals.activities.store', $deal) }}" class="crm-form-grid" data-crm-ajax-form data-crm-reload-region="crm-deal-timeline" data-crm-reset-on-success>
                        @csrf
                        <x-admin-panel::select name="type" label="Type" :options="array_intersect_key($activityTypes, array_flip(['note', 'call', 'email', 'meeting']))" selected="note" required />
                        <x-admin-panel::input name="subject" label="Subject" required />
                        <x-admin-panel::input name="occurred_at" label="Occurred At" type="datetime-local" />
                        <x-admin-panel::textarea name="body" label="Body" class="crm-span-2" rows="4" />
                        <div class="crm-form-actions crm-span-2">
                            <x-admin-panel::button type="submit" icon="message-square">Add Activity</x-admin-panel::button>
                        </div>
                    </form>
                @endcan
            </x-admin-panel::card>

            <x-admin-panel::card id="crm-deal-timeline">
                <x-slot:header>
                    Activity Timeline
                </x-slot:header>

                <form method="GET" action="{{ route('crm.deals.show', $deal) }}" class="crm-inline-form">
                    <x-admin-panel::select name="activity_type" label="Filter" :options="$activityTypes" :selected="$activityFilter" placeholder="{{ __('All activity') }}" />
                    <x-admin-panel::button type="submit" icon="filter">Apply</x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.deals.show', $deal)" variant="ghost">Reset</x-admin-panel::button>
                </form>

                @include('crm::admin.partials._timeline')
            </x-admin-panel::card>
        </div>
    </section>
@endsection
