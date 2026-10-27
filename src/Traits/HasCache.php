<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Traits;

use Essa\APIToolKit\Enum\CacheKeys;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** @phpstan-require-extends Model */
trait HasCache
{
    protected static function bootHasCache(): void
    {
        $flush = static function (self $model): void {
            $model->flushCache();
        };
        static::updated($flush);
        static::created($flush);
        static::deleted($flush);
    }

    public function flushCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    protected function cacheKey(): string
    {
        return CacheKeys::DEFAULT_CACHE_KEY->value;
    }
}
