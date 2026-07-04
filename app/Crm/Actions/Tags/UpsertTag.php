<?php

namespace App\Crm\Actions\Tags;

use App\Crm\Models\Tag;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class UpsertTag
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Tag $tag, array $payload, ?Authenticatable $user = null): Tag
    {
        $payload['slug'] = Str::slug($payload['slug'] ?: $payload['name']);
        $payload[$tag->exists ? 'updated_by' : 'created_by'] = $user?->getAuthIdentifier();

        $tag->fill($payload);
        $tag->save();

        return $tag->refresh();
    }
}
