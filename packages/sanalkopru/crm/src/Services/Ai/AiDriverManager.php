<?php

namespace Sanalkopru\Crm\Services\Ai;

use InvalidArgumentException;
use Sanalkopru\Crm\Contracts\AiProviderContract;
use Sanalkopru\Crm\Services\Ai\Providers\ClaudeProvider;
use Sanalkopru\Crm\Services\Ai\Providers\GeminiProvider;
use Sanalkopru\Crm\Services\Ai\Providers\NullAiProvider;
use Sanalkopru\Crm\Services\Ai\Providers\OpenAiProvider;
use Sanalkopru\Crm\Services\Settings\CrmSettingsManager;
use Sanalkopru\Crm\Support\Ai\AiDriver;

class AiDriverManager
{
    public function __construct(private readonly CrmSettingsManager $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('ai_enabled', config('crm.ai.enabled', false));
    }

    public function available(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        if ($this->selected() === AiDriver::Null) {
            return false;
        }

        return $this->apiKey() !== null;
    }

    public function selected(): AiDriver
    {
        $driver = (string) $this->settings->get('ai_driver', config('crm.ai.driver', config('crm.ai.provider', AiDriver::OpenAI->value)));

        return AiDriver::tryFrom($driver)
            ?? throw new InvalidArgumentException(sprintf(
                'Unsupported CRM AI driver [%s]. Supported drivers: %s.',
                $driver,
                implode(', ', AiDriver::values())
            ));
    }

    /**
     * @return array<string, mixed>
     */
    public function config(?AiDriver $driver = null): array
    {
        $driver ??= $this->selected();

        return (array) config("crm.ai.drivers.{$driver->value}", []);
    }

    public function model(?AiDriver $driver = null): ?string
    {
        $model = $this->settings->get('ai_model') ?: ($this->config($driver)['model'] ?? config('crm.ai.model'));

        return $model ? (string) $model : null;
    }

    public function maxTokens(): int
    {
        return (int) config('crm.ai.max_tokens', 1200);
    }

    public function temperature(): float
    {
        return (float) config('crm.ai.temperature', 0.3);
    }

    public function apiKey(?AiDriver $driver = null): ?string
    {
        $apiKey = $this->config($driver)['api_key'] ?? null;

        return $apiKey ? (string) $apiKey : null;
    }

    public function provider(): AiProviderContract
    {
        return match ($this->selected()) {
            AiDriver::OpenAI => app(OpenAiProvider::class),
            AiDriver::Claude => app(ClaudeProvider::class),
            AiDriver::Gemini => app(GeminiProvider::class),
            AiDriver::Null => app(NullAiProvider::class),
        };
    }
}
