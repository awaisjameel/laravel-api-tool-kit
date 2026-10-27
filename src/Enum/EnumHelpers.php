<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Enum;

use BackedEnum;

/** @phpstan-require-implements BackedEnum */
trait EnumHelpers
{
    /** @return list<string|int> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    /** @return list<string> */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }
    public static function isValid(mixed $value): bool
    {
        return (is_int($value) || is_string($value)) && self::fromValue($value) !== null;
    }
    public static function fromValue(string|int $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        return null;
    }
    /** @return array<string, string|int> */
    public static function toArray(): array
    {
        return array_combine(self::names(), self::values());
    }
}
