<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator;

use Illuminate\Support\Str;

final class ModelNaming
{
    public static function tableName(string $model): string
    {
        return Str::snake(Str::pluralStudly($model));
    }
    public static function routeName(string $model): string
    {
        return Str::kebab(Str::plural($model));
    }
}
