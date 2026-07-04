<?php

namespace App\Crm\Services\Ai\Providers;

use App\Crm\Contracts\AiProviderContract;
use App\Crm\Services\Ai\AiDriverManager;
use App\Crm\Services\Ai\Providers\Concerns\BuildsAiPrompts;
use App\Crm\Support\Ai\AiDriver;
use Illuminate\Support\Facades\Http;

class OpenAiProvider implements AiProviderContract
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
        $config = $this->manager->config(AiDriver::OpenAI);
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.openai.com/v1'), '/');
        $headers = array_filter([
            'Authorization' => 'Bearer '.($config['api_key'] ?? ''),
            'OpenAI-Organization' => $config['organization'] ?? null,
            'OpenAI-Project' => $config['project'] ?? null,
        ]);

        $response = Http::timeout((int) ($config['request_timeout'] ?? 30))
            ->withHeaders($headers)
            ->post($baseUrl.'/chat/completions', [
                'model' => $this->manager->model(AiDriver::OpenAI),
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt($context)],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $this->manager->maxTokens(),
                'temperature' => $this->manager->temperature(),
            ])
            ->throw()
            ->json();

        return trim((string) data_get($response, 'choices.0.message.content'));
    }
}
