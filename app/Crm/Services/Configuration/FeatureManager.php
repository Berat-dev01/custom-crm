<?php

namespace App\Crm\Services\Configuration;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FeatureManager
{
    public function enabled(string $module): bool
    {
        return (bool) config("crm.modules.{$module}", false);
    }

    public function disabled(string $module): bool
    {
        return ! $this->enabled($module);
    }

    /**
     * @return list<string>
     */
    public function enabledModules(): array
    {
        return array_keys(array_filter(
            (array) config('crm.modules', []),
            static fn (mixed $enabled): bool => (bool) $enabled
        ));
    }

    public function ensureEnabled(string $module): void
    {
        if ($this->disabled($module)) {
            throw new NotFoundHttpException("CRM module [{$module}] is disabled.");
        }
    }
}
