<?php

namespace Sanalkopru\Crm\Services\Ai\Providers\Concerns;

trait BuildsAiPrompts
{
    protected function userPrompt(string $task, string $content, array $context): string
    {
        $lines = [
            'Task:',
            $task,
            '',
            'CRM context:',
            json_encode($context['crm'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            '',
            'User input or source content:',
            $content,
        ];

        return implode("\n", $lines);
    }

    protected function systemPrompt(array $context): string
    {
        return (string) ($context['system'] ?? 'You are a CRM sales assistant.');
    }
}
