<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

if (! function_exists('dateTimeFormat')) {
    /**
     * Format a datetime value using Carbon according to the specified format.
     *
     * @param DateTimeInterface|string|null $value The datetime value to format.
     * @param string|null $format The optional format to use. If not provided, the default format from the configuration will be used.
     * @return string|null The formatted datetime value or null if the input value is null.
     */
    function dateTimeFormat(DateTimeInterface|string|null $value, ?string $format = null): ?string
    {
        $format ??= config('api-tool-kit.datetime_format');

        return $value !== null && $value !== '' ? CarbonImmutable::parse($value)->format($format) : null;
    }
}

if (! function_exists('createOrRandomFactory')) {
    /**
     * Create a new instance of a model using its factory if no records exist, or return a random record if records exist.
     *
     * @param class-string<Model> $className The name of the Eloquent model class.
     * @return Model The created model instance or a random model instance.
     */
    function createOrRandomFactory(string $className): Model
    {
        $existing = $className::query()->inRandomOrder()->first();
        if ($existing !== null) {
            return $existing;
        }
        if (!method_exists($className, 'factory')) {
            throw new LogicException('The model must use HasFactory.');
        }
        $created = $className::factory()->create();
        if (!$created instanceof Model) {
            throw new UnexpectedValueException('Expected a single model from its factory.');
        }
        return $created;
    }
}
