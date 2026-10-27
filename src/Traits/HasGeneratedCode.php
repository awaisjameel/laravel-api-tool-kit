<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @method static findByCode($code)
 * @phpstan-require-extends Model
 */
trait HasGeneratedCode
{
    protected static function bootHasGeneratedCode(): void
    {
        static::creating(function (self $model): void {
            $model->refreshCode();
        });
    }

    /** @param Builder<static> $query
     * @return static */
    public function scopeFindByCode(Builder $query, string $code): Model
    {
        return $query->where($this->codeField(), $code)
            ->firstOrFail();
    }

    public function refreshCode(): void
    {
        $this->{$this->codeField()} = $this->generateCode();
    }

    public function generateCode(): string
    {
        $code = Str::random(10);

        if ($this->where($this->codeField(), $code)->exists()) {
            return $this->generateCode();
        }

        return $code;
    }

    protected function codeField(): string
    {
        return 'code';
    }
}
