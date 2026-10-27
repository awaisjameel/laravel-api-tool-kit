<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Guessers;

use Essa\APIToolKit\Generator\ColumnDefinition;
use Essa\APIToolKit\Generator\Contracts\GuesserInterface;
use Essa\APIToolKit\Generator\Helpers\PhpLiteral;

class ValidationRuleGuesserInterface implements GuesserInterface
{
    /**
     * @param list<string> $extraValidation
     */
    public function __construct(private ColumnDefinition $definition, private array $extraValidation)
    {
    }

    public function guess(): string
    {
        $rules = $this->addExtraValidationToTheRules();

        if ($this->definition->isEnumType()) {
            $rules[] = 'in:' . implode(',', $this->definition->getEnumValues());
        }

        if ($this->definition->isEmailType()) {
            $rules[] = 'email';
        }

        if ($this->definition->isImageType()) {
            $rules[] = 'image';
        }

        if ($this->definition->isStringOrTextType()) {
            $rules[] = 'string';
        }

        if ($this->definition->isIntegerType()) {
            $rules[] = 'integer';
        }

        if ($this->definition->isBooleanType()) {
            $rules[] = 'boolean';
        }

        if ($this->definition->isNumericType()) {
            $rules[] = 'numeric';
        }

        if ($this->definition->isDateType()) {
            $rules[] = 'date';
        }

        if ($this->definition->isTimeType()) {
            $rules[] = 'date_format:H:i:s';
        }

        if ($this->definition->isUuidType()) {
            $rules[] = 'uuid';
        }

        if ($this->definition->isIpAddressType()) {
            $rules[] = 'ip';
        }

        if ($this->definition->isJsonType()) {
            $rules[] = 'json';
        }

        return PhpLiteral::stringList($rules);
    }

    /**
     * @return list<string>
     */
    private function addExtraValidationToTheRules(): array
    {
        if (!in_array('nullable', $this->definition->getOptions(), true)) {
            return $this->extraValidation;
        }

        return $this->extraValidation === ['required'] ? ['nullable'] : [...$this->extraValidation, 'nullable'];
    }
}
