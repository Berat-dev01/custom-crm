<?php

namespace App\Crm\Services\Ai\Providers;

use App\Crm\Contracts\AiProviderContract;
use App\Crm\Services\Ai\AiDriverManager;
use App\Crm\Services\Ai\Providers\Concerns\BuildsAiPrompts;
use App\Crm\Support\Ai\AiDriver;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProviderContract
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
        $config = $this->manager->config(AiDriver::Gemini);
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $model = $this->manager->model(AiDriver::Gemini) ?: 'gemini-1.5-flash';
        $response = Http::timeout((int) ($config['request_timeout'] ?? 30))
            ->post($baseUrl.'/models/'.$model.':generateContent?key='.urlencode((string) ($config['api_key'] ?? '')), [
                'systemInstruction' => [
                    'parts' => [['text' => $this->systemPrompt($context)]],
                ],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => $this->manager->maxTokens(),
                    'temperature' => $this->manager->temperature(),
                ],
            ])
            ->throw()
            ->json();

        return trim((string) data_get($response, 'candidates.0.content.parts.0.text'));
    }
}
