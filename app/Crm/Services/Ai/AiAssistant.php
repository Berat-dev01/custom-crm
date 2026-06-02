<?php

namespace App\Crm\Services\Ai;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Crm\Contracts\AiProviderContract;
use App\Crm\Models\Activity;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use App\Crm\Support\Ai\AiResult;
use Throwable;

class AiAssistant
{
    public function __construct(
        private readonly AiProviderContract $provider,
        private readonly AiDriverManager $manager,
        private readonly PromptTemplates $prompts
    ) {}

    public function summarizeNote(?Activity $activity, ?string $content = null): AiResult
    {
        return $this->run(fn (): string => $this->provider->summarize(
            $this->clean($content ?: trim(($activity?->subject ?? '')."\n".($activity?->body ?? ''))),
            $this->context($this->prompts->summarizeNote(), [
                'activity_type' => $activity?->type,
                'subject' => $activity?->subject,
                'occurred_at' => $activity?->occurred_at?->toDateTimeString(),
            ])
        ));
    }

    public function summarizeDealTimeline(Deal $deal): AiResult
    {
        $deal->loadMissing(['stage', 'company', 'activities.user']);
        $timeline = $deal->activities()
            ->with('user')
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get()
            ->map(fn (Activity $activity): array => [
                'type' => $activity->type,
                'subject' => $this->clean($activity->subject, 160),
                'body' => $this->clean($activity->body, 360),
                'occurred_at' => $activity->occurred_at?->toDateTimeString(),
            ])
            ->all();

        return $this->run(fn (): string => $this->provider->summarize(
            'Summarize this bounded deal timeline.',
            $this->context($this->prompts->summarizeDealTimeline(), [
                'deal' => $this->dealContext($deal),
                'timeline' => $timeline,
            ])
        ));
    }

    public function draftDealEmail(?Deal $deal, string $brief): AiResult
    {
        $deal?->loadMissing(['stage', 'company', 'contact']);

        return $this->run(fn (): string => $this->provider->draftEmail(
            $this->clean($brief),
            $this->context($this->prompts->draftEmail(), [
                'deal' => $deal ? $this->dealContext($deal) : null,
            ])
        ));
    }

    public function draftQuoteFollowUp(Quote $quote, string $brief = ''): AiResult
    {
        $quote->loadMissing(['company', 'contact', 'deal', 'items']);

        return $this->run(fn (): string => $this->provider->draftFollowUp(
            $this->clean($brief ?: 'Follow up on this quote.'),
            $this->context($this->prompts->draftFollowUp(), [
                'quote' => [
                    'quote_number' => $quote->quote_number,
                    'status' => $quote->status,
                    'currency' => $quote->currency,
                    'grand_total' => (string) $quote->grand_total,
                    'valid_until' => $quote->valid_until?->toDateString(),
                    'customer' => $quote->company?->name ?: $quote->contact?->full_name,
                    'deal_title' => $quote->deal?->title,
                    'items' => $quote->items->take(8)->map(fn ($item): array => [
                        'name' => $this->clean($item->name, 120),
                        'quantity' => (string) $item->quantity,
                        'line_total' => (string) $item->line_total,
                    ])->all(),
                ],
            ])
        ));
    }

    public function analyzeLostDeal(Deal $deal): AiResult
    {
        $deal->loadMissing(['stage', 'company', 'activities']);

        return $this->run(fn (): string => $this->provider->analyzeLostDeal(
            $this->clean($deal->lost_reason ?: 'No lost reason was recorded.'),
            $this->context($this->prompts->lostDealAnalysis(), [
                'deal' => $this->dealContext($deal),
                'recent_activity_subjects' => $deal->activities()
                    ->orderByDesc('occurred_at')
                    ->limit(10)
                    ->pluck('subject')
                    ->map(fn (?string $subject): string => $this->clean($subject, 160))
                    ->all(),
            ])
        ));
    }

    private function run(callable $callback): AiResult
    {
        if (! $this->manager->available()) {
            return AiResult::unavailable(trans('crm::messages.ai.not_configured'));
        }

        try {
            $content = trim((string) $callback());

            return $content !== ''
                ? AiResult::success($content)
                : AiResult::failure(trans('crm::messages.ai.empty_draft'));
        } catch (Throwable $e) {
            Log::error('CRM AI request failed', [
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);

            return AiResult::failure(trans('crm::messages.ai.request_failed_retry'));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function context(string $task, array $crm): array
    {
        return [
            'system' => $this->prompts->system(),
            'task' => $task,
            'crm' => $crm,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dealContext(Deal $deal): array
    {
        return [
            'title' => $this->clean($deal->title, 180),
            'company' => $deal->company?->name,
            'contact_name' => $deal->contact?->full_name,
            'stage' => $deal->stage?->name,
            'status' => $deal->status,
            'value' => (string) $deal->value,
            'currency' => $deal->currency,
            'probability' => $deal->probability,
            'expected_close_date' => $deal->expected_close_date?->toDateString(),
            'lost_reason' => $this->clean($deal->lost_reason, 300),
        ];
    }

    private function clean(?string $value, int $limit = 1800): string
    {
        $value = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $value)));

        return Str::limit($value, $limit, '');
    }
}
