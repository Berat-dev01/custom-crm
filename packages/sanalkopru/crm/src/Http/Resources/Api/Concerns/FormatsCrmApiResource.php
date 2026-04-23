<?php

namespace Sanalkopru\Crm\Http\Resources\Api\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\MissingValue;
use Sanalkopru\Crm\Models\Tag;

trait FormatsCrmApiResource
{
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
