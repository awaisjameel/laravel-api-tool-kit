<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @phpstan-require-extends Model */
trait HasCreatedBy
{
    protected static function bootHasCreatedBy(): void
    {
        static::creating(function (self $model): void {
            if (auth()->check()) {
                $model->setAttribute('created_by', auth()->id());
            }
        });
    }

    /** @return BelongsTo<Model, $this> */
    public function createdBy(): BelongsTo
    {
        $userClass = config('auth.providers.users.model');
        if (!is_string($userClass) || !is_a($userClass, Model::class, true)) {
            throw new \InvalidArgumentException('Configure an Eloquent user model for createdBy.');
        }
        return $this->belongsTo($userClass, 'created_by');
    }
}
