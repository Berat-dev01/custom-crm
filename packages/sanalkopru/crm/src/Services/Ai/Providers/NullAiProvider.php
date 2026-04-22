<?php

namespace Sanalkopru\Crm\Services\Ai\Providers;

use Sanalkopru\Crm\Contracts\AiProviderContract;

class NullAiProvider implements AiProviderContract
{
    public function summarize(string $content, array $context = []): string
    {
        return 'AI is disabled.';
    }

    public function draftEmail(string $brief, array $context = []): string
    {
        return 'AI is disabled.';
    }

    public function draftFollowUp(string $brief, array $context = []): string
    {
        return 'AI is disabled.';
    }

    public function analyzeLostDeal(string $brief, array $context = []): string
    {
        return 'AI is disabled.';
    }
}
