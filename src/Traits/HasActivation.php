<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * @method static active()
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait HasActivation
{
    /**
     * fetch all records that is active.
     *
     * @param Builder<static> $query
     * @return Builder<static> */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }

    /**
     *toggle model status (active - deactivate ).
     */
    public function toggleActivation(): void
    {
        $this->is_active = ! $this->is_active;

        $this->save();
    }
}
