<?php

namespace Sanalkopru\Crm\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Sanalkopru\Crm\Services\Configuration\MoneySettings;

class CrmFormatter
{
    public function __construct(
        private readonly MoneySettings $money,
        private readonly CrmLabelCatalog $labels
    ) {}

    public function money(float|int|string|null $amount, ?string $currency = null): string
    {
        $currency ??= $this->money->defaultCurrency();

        return number_format((float) ($amount ?? 0), 2, ',', '.').' '.$currency;
    }

    public function date(DateTimeInterface|string|null $value): string
    {
        $date = $this->carbon($value);

        return $date ? $date->format('d.m.Y') : '-';
    }

    public function datetime(DateTimeInterface|string|null $value): string
    {
        $date = $this->carbon($value);

        return $date ? $date->format('d.m.Y H:i') : '-';
    }

    public function status(string $status): string
    {
        return $this->labels->status($status);
    }

    public function activityType(string $type): string
    {
        return $this->labels->activityTypes()[$type] ?? $this->status($type);
    }

    private function carbon(DateTimeInterface|string|null $value): ?CarbonInterface
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse($value);
    }
}
