<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Traits;

use Illuminate\Database\Eloquent\Builder;

/** @phpstan-require-extends \Illuminate\Database\Eloquent\Model */
trait HasOrder
{
    /** @param array<int, int|string> $sort */
    public static function sort(array $sort): void
    {
        static::query()->getModel()->getConnection()->transaction(static function () use ($sort): void {
            foreach ($sort as $order => $modelId) {
                $model = static::query()->findOrFail($modelId);
                $model->setAttribute('order', $order);
                $model->saveOrFail();
            }
        });
    }

    protected static function bootHasOrder(): void
    {
        static::addGlobalScope('order', function (Builder $builder): void {
            $builder->orderBy('order', 'asc');
        });
    }
}
