<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Helpers;

final class PhpLiteral
{
    /** @param list<string> $values */
    public static function stringList(array $values): string
    {
        return implode(', ', array_map(static fn (string $value): string => var_export($value, true), $values));
    }
}
