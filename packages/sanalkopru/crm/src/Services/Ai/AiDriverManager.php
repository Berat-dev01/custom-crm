<?php

namespace Sanalkopru\Crm\Services\Ai;

use InvalidArgumentException;
use Sanalkopru\Crm\Support\Ai\AiDriver;

class AiDriverManager
{
    public function enabled(): bool
    {
        return (bool) config('crm.ai.enabled', false);
    }

    public function selected(): AiDriver
    {
        $driver = (string) config('crm.ai.driver', AiDriver::OpenAI->value);

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
        $model = $this->config($driver)['model'] ?? config('crm.ai.model');

        return $model ? (string) $model : null;
    }
}
