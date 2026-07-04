<?php

namespace App\Crm\Services\Ai\Providers;

use App\Crm\Contracts\AiProviderContract;
use App\Crm\Services\Ai\AiDriverManager;
use App\Crm\Services\Ai\Providers\Concerns\BuildsAiPrompts;
use App\Crm\Support\Ai\AiDriver;
use Illuminate\Support\Facades\Http;

class ClaudeProvider implements AiProviderContract
{
    use BuildsAiPrompts;

    public function __construct(private readonly AiDriverManager $manager) {}

    public function summarize(string $content, array $context = []): string
    {
        return $this->complete($context, $this->userPrompt((string) $context['task'], $content, $context));
    }

    public function draftEmail(string $brief, array $context = []): string
    {
        return $this->complete($context, $this->userPrompt((string) $context['task'], $brief, $context));
    }

    public function draftFollowUp(string $brief, array $context = []): string
    {
        return $this->complete($context, $this->userPrompt((string) $context['task'], $brief, $context));
    }

    public function analyzeLostDeal(string $brief, array $context = []): string
    {
        return $this->complete($context, $this->userPrompt((string) $context['task'], $brief, $context));
    }

    private function complete(array $context, string $prompt): string
    {
        $config = $this->manager->config(AiDriver::Claude);
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.anthropic.com/v1'), '/');
        $response = Http::timeout((int) ($config['request_timeout'] ?? 30))
            ->withHeaders([
                'x-api-key' => (string) ($config['api_key'] ?? ''),
                'anthropic-version' => '2023-06-01',
            ])
            ->post($baseUrl.'/messages', [
                'model' => $this->manager->model(AiDriver::Claude),
                'system' => $this->systemPrompt($context),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $this->manager->maxTokens(),
                'temperature' => $this->manager->temperature(),
            ])
            ->throw()
            ->json();

        return trim((string) data_get($response, 'content.0.text'));
    }
}
