<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Enum;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

/**
 * Legacy constant enum retained for the fork's Eloquent casts. Prefer backed enums for new code.
 *
 * Example (in a model casts array):
 *   'platform_name' => \App\Enums\Product\PricePlatform::class,
 *
 * Accepts either constant VALUE or constant NAME when setting.
 * Returns the stored scalar value when getting.
 * Throws InvalidArgumentException for invalid assignments.
 */
abstract class Enum implements Castable
{
    /**
     * Get the values of every constant in the enum.
     *
     * @return list<mixed>
     */
    final public static function getAll(): array
    {
        return array_values(static::toArray());
    }

    /**
     * Convert the enum to an associative array of constant names and values.
     *
     * @return array<string, mixed>
     */
    final public static function toArray(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }

    /**
     * Get the constant names of the enum.
     *
     * @return list<string>
     */
    final public static function getConst(): array
    {
        return array_keys(static::toArray());
    }

    /**
     * Check if a given value is a valid value of the enum.
     *
     * @param mixed $value The value to be checked.
     * @return bool
     */
    final public static function isValid(mixed $value): bool
    {
        return in_array($value, static::toArray(), true);
    }

    /**
     * Check if a given value is a valid constant of the enum.
     *
     * @param mixed $value The value to be checked.
     * @return bool
     */
    final public static function isValidConst(mixed $value): bool
    {
        return in_array($value, static::getConst(), true);
    }

    /**
     * Get the value of a given constant of the enum.
     *
     * @param string $const The constant name.
     * @return mixed|null The value of the constant, or null if the constant does not exist.
     */
    final public static function getValue(string $const): mixed
    {
        return static::toArray()[$const] ?? null;
    }

    /**
     * Enable direct casting via Eloquent by returning a caster instance.
     *
     * @param array<array-key, mixed> $arguments
     * @return CastsAttributes<mixed, mixed> */
    public static function castUsing(array $arguments): CastsAttributes
    {
        $enumClass = static::class;

        /** @implements CastsAttributes<mixed, mixed> */
        return new class ($enumClass) implements CastsAttributes {
            /** @param class-string<Enum> $enumClass */
            public function __construct(private string $enumClass)
            {
            }

            /** @param array<string, mixed> $attributes */
            public function get(Model $model, string $key, mixed $value, array $attributes): mixed
            {
                // Return raw stored value (already validated on set).
                return $value;
            }

            /** @param array<string, mixed> $attributes
             * @return array<string, mixed> */
            public function set(Model $model, string $key, mixed $value, array $attributes): array
            {
                $enum = $this->enumClass;
                if ($value === null) {
                    return [$key => null];
                }

                // Allow passing an already valid underlying value.
                if ($enum::isValid($value)) {
                    return [$key => $value];
                }

                // Allow passing the constant NAME (e.g. 'ChargeBee').
                if (is_string($value) && $enum::isValidConst($value)) {
                    return [$key => $enum::getValue($value)];
                }

                throw new \InvalidArgumentException('Invalid ' . get_debug_type($value) . " value for enum {$enum}.");
            }
        };
    }
}
