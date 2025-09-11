<?php

namespace Essa\APIToolKit\Enum;

use ReflectionClass;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Base Enum class extended to support direct usage in Laravel model casts.
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
     * @return array
     */
    final public static function getAll(): array
    {
        return array_values(static::toArray());
    }

    /**
     * Convert the enum to an associative array of constant names and values.
     *
     * @return array
     */
    final public static function toArray(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }

    /**
     * Get the constant names of the enum.
     *
     * @return array
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
        return in_array($value, static::toArray());
    }

    /**
     * Check if a given value is a valid constant of the enum.
     *
     * @param mixed $value The value to be checked.
     * @return bool
     */
    final public static function isValidConst(mixed $value): bool
    {
        return in_array($value, static::getConst());
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
     */
    public static function castUsing(array $arguments)
    {
        $enumClass = static::class;

        return new class($enumClass) implements CastsAttributes {
            public function __construct(private string $enumClass) {}

            public function get($model, string $key, $value, array $attributes)
            {
                // Return raw stored value (already validated on set).
                return $value;
            }

            public function set($model, string $key, $value, array $attributes)
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

                throw new \InvalidArgumentException("Invalid value '{$value}' for enum {$enum}.");
            }
        };
    }
}
