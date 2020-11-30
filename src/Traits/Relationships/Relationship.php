<?php

namespace Shortcodes\Toolbox\Traits\Relationships;

use Illuminate\Support\Str;
use Shortcodes\Toolbox\Observers\RelationObserver;

trait Relationship
{
    public array $relationshipsInRequest = [];

    public function initializeRelationship()
    {
        $relationRelatedFillables = [];

        collect($this->relationTypes)->map(function ($relation) use ($relationRelatedFillables) {
            $relationRelatedFillables[] = Str::snake($relation);
        });

        $this->fillable = array_merge($this->fillable, $relationRelatedFillables);
    }

    public static function bootRelationship()
    {
        static::observe(RelationObserver::class);
    }
}
