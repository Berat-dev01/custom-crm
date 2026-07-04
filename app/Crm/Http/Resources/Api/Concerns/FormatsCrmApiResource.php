<?php

namespace App\Crm\Http\Resources\Api\Concerns;

use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use App\Crm\Models\Tag;
use App\Crm\Support\CrmFormatter;
use App\Crm\Support\CrmLabelCatalog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\MissingValue;

trait FormatsCrmApiResource
{
    protected function crmFormatter(): CrmFormatter
    {
        return app(CrmFormatter::class);
    }

    protected function crmLabels(): CrmLabelCatalog
    {
        return app(CrmLabelCatalog::class);
    }

    protected function labelFor(?string $value): ?string
    {
        return filled($value) ? $this->crmFormatter()->status($value) : null;
    }

    protected function relatedRecordTypeKey(?string $modelClass): ?string
    {
        return match ($modelClass) {
            Contact::class => 'contact',
            Company::class => 'company',
            Deal::class => 'deal',
            Quote::class => 'quote',
            default => null,
        };
    }

    protected function relatedRecordTypeLabel(?string $modelClass): ?string
    {
        return $this->crmLabels()->relatedRecordTypeLabelFromModel($modelClass);
    }

    /**
     * @return array{id: int, name: string|null}|null
     */
    protected function userSummary(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    /**
     * @param  Collection<int, Tag>|MissingValue  $tags
     * @return list<array{id: int, name: string, color: string|null}>|MissingValue
     */
    protected function tagSummaries(Collection|MissingValue $tags): array|MissingValue
    {
        if ($tags instanceof MissingValue) {
            return $tags;
        }

        return $tags
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])
            ->values()
            ->all();
    }
}
