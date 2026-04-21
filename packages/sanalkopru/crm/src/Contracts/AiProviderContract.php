<?php

namespace Sanalkopru\Crm\Contracts;

interface AiProviderContract
{
    public function summarize(string $content, array $context = []): string;

    public function draftEmail(string $brief, array $context = []): string;

    public function draftFollowUp(string $brief, array $context = []): string;
}
