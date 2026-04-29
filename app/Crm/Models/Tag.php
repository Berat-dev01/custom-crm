<?php

namespace App\Crm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Crm\Database\Factories\TagFactory;
use App\Crm\Models\Concerns\HasPublicId;

class Tag extends Model
{
    use HasFactory;
    use HasPublicId;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(Contact::class, 'taggable', 'tag_relations');
    }

    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'taggable', 'tag_relations');
    }

    public function deals(): MorphToMany
    {
        return $this->morphedByMany(Deal::class, 'taggable', 'tag_relations');
    }

    public function quotes(): MorphToMany
    {
        return $this->morphedByMany(Quote::class, 'taggable', 'tag_relations');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('slug', 'like', "%{$term}%");
    }
}
