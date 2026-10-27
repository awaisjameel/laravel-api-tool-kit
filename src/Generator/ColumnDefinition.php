<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator;

class ColumnDefinition
{
    /**
     * @param list<string> $options
     */
    public function __construct(private string $name, private string $type, private array $options)
    {
    }

    public static function createFromDefinitionString(string $columnDefinitions): ColumnDefinition
    {
        [$name, $type, $options] = self::parseColumnDefinition($columnDefinitions);

        return new ColumnDefinition($name, $type, $options);
    }

    /**
     * @return array{string, string, list<string>}
     */
    protected static function parseColumnDefinition(string $definition): array
    {
        $parts = explode(':', $definition);
        $name = array_shift($parts);
        $type = count($parts) > 0 ? $parts[0] : 'string';
        $options = array_slice($parts, 1);

        return [$name, $type, $options];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        if ($this->isEnumType()) {
            return 'enum';
        }

        return match ($type = explode('(', $this->type, 2)[0]) {
            'int' => 'integer', 'varchar' => 'string', 'datetime' => 'dateTime', default => $type
        };
    }

    /**
     * @return list<string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOptionsAsString(): string
    {
        return implode(' , ', $this->options);
    }

    public function isForeignKey(): bool
    {
        return 'foreignId' === $this->getType();
    }

    public function isEnumType(): bool
    {
        return str_starts_with($this->type, 'enum(');
    }

    /**
     * @return list<string>
     */
    public function getEnumValues(): array
    {
        return array_map(static function (string $value): string {
            $value = trim($value);
            if (strlen($value) >= 2 && in_array($value[0], ["'", '"'], true) && $value[0] === $value[strlen($value) - 1]) {
                $value = substr($value, 1, -1);
            }
            // Blueprint embeds enum values directly in DDL. Keep the compact
            // schema syntax portable across its supported database grammars.
            if (preg_match('/^[A-Za-z0-9_-]+$/D', $value) !== 1) {
                throw new \InvalidArgumentException('Schema enum values must contain only letters, digits, underscores or hyphens.');
            }
            return $value;
        }, explode(',', substr($this->type, 5, -1)));
    }

    /** @return list<int> */
    public function getTypeArguments(): array
    {
        if ($this->isEnumType() || !str_contains($this->type, '(')) {
            return [];
        }
        $arguments = substr($this->type, strpos($this->type, '(') + 1, -1);
        if (preg_match('/^\d+(?:,\d+)*$/D', $arguments) !== 1) {
            throw new \InvalidArgumentException('Column type arguments must be non-negative integers.');
        }
        return array_map(intval(...), explode(',', $arguments));
    }

    public function isEmailType(): bool
    {
        return str_contains($this->name, 'email');
    }

    public function isImageType(): bool
    {
        return str_contains($this->name, 'image');
    }

    public function isBooleanType(): bool
    {
        return 'boolean' === $this->type;
    }

    public function isUuidType(): bool
    {
        return in_array($this->getType(), ['uuid', 'uuidBinary']);
    }

    public function isIpAddressType(): bool
    {
        return 'ipAddress' === $this->type;
    }

    public function isJsonType(): bool
    {
        return in_array($this->getType(), ['json', 'jsonb']);
    }

    public function isIntegerType(): bool
    {
        return in_array($this->getType(), ['integer', 'bigInteger', 'unsignedBigInteger', 'mediumInteger', 'tinyInteger', 'smallInteger', 'foreignId'], true);
    }

    public function isNumericType(): bool
    {
        return in_array($this->getType(), ['decimal', 'double', 'float']);
    }

    public function isStringOrTextType(): bool
    {
        return in_array($this->getType(), ['string', 'text']) && ! $this->isImageType();
    }

    public function isDateType(): bool
    {
        return in_array($this->getType(), ['date', 'dateTime', 'dateTimeTz', 'timestamp', 'timestampTz' , 'datetime']);
    }

    public function isTimeType(): bool
    {
        return in_array($this->getType(), ['time', 'timeTz']);
    }
}
