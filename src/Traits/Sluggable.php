<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @method static findBySlug($slug)
 * @phpstan-require-extends Model
 */
trait Sluggable
{
    protected static function bootSluggable(): void
    {
        static::creating(function (self $model): void {
            if ($model->enableSluggableInCreating()) {
                $model->generateSlug();
            }
        });

        static::updating(function (self $model): void {
            if ($model->enableSluggableInUpdating()) {
                $model->generateSlug();
            }
        });
    }

    /**
     * find model by slug.
     *
     * @param Builder<static> $query
     * @return static */
    public function scopeFindBySlug(Builder $query, string $slug): Model
    {
        return $query->where($this->getSlugFieldName(), $slug)->firstOrFail();
    }

    public function generateSlug(): void
    {
        $this->{$this->getSlugFieldName()} = Str::slug($this->{$this->getSlugSourceName()});
    }

    protected function enableSluggableInCreating(): bool
    {
        return true;
    }

    protected function enableSluggableInUpdating(): bool
    {
        return true;
    }

    protected function getSlugFieldName(): string
    {
        return 'slug';
    }

    protected function getSlugSourceName(): string
    {
        return 'name';
    }
}
