<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Traits;

/** @phpstan-require-extends \Illuminate\Foundation\Http\FormRequest */
trait IncludeTranslation
{
    /** @return array<array-key, mixed> */
    public function validatedWithTranslation(): array
    {
        $request = $this->validated();

        if (! array_key_exists('translations', $request)) {
            return $request;
        }

        $name_translations = $request['translations'];
        if (!is_array($name_translations)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['translations' => 'Translations must be an array.']);
        }

        unset($request['translations']);

        return array_merge($request, $name_translations);
    }
}
