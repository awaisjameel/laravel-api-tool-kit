<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator;

use Essa\APIToolKit\Generator\Exception\SchemaNotValidException;

class SchemaDefinition
{
    /**
     * @param list<ColumnDefinition> $columns
     */
    public function __construct(private array $columns)
    {
    }

    /**
     * Create a SchemaDefinition object from a schema string.
     *
     * @param string|null $schema The schema string to parse.
     *
     * @return SchemaDefinition Returns a SchemaDefinition object.
     * @throws SchemaNotValidException
     */
    public static function createFromSchemaString(?string $schema): SchemaDefinition
    {
        if (! $schema) {
            return new self([]);
        }

        self::validateSchema($schema);

        $columnDefinitions = explode('|', $schema);

        $columns = [];

        foreach ($columnDefinitions as $columnDefinition) {
            $columns[] = ColumnDefinition::createFromDefinitionString($columnDefinition);
        }

        return new self($columns);
    }

    /**
     * Validates a schema string of the format "COLUMN_NAME:COLUMN_TYPE:OPTIONS|..."
     *
     * Names are identifiers; types accept numeric arguments or simple enum values.
     * Additional colon-separated modifiers use Blueprint method syntax.
     *
     * @param string $schema
     * @return void throw Exception if schema not valid.
     * @throws SchemaNotValidException
     */
    private static function validateSchema(string $schema): void
    {
        $pattern = '~^[A-Za-z_][A-Za-z0-9_]*:[A-Za-z_][A-Za-z0-9_]*(?:\([0-9a-zA-Z_,\'"\\-]*\))?(?::[A-Za-z_][A-Za-z0-9_]*(?:\([0-9a-zA-Z_,\'"\\-]*\))?)*$~D';
        foreach (explode('|', $schema) as $column) {
            if (preg_match($pattern, $column) !== 1) {
                throw new SchemaNotValidException();
            }
            $definition = ColumnDefinition::createFromDefinitionString($column);
            try {
                if ($definition->isEnumType()) {
                    $definition->getEnumValues();
                } else {
                    $definition->getTypeArguments();
                }
            } catch (\InvalidArgumentException $exception) {
                throw new SchemaNotValidException($exception);
            }
        }
    }

    /**
     * Get the array of ColumnDefinition objects.
     *
     * @return list<ColumnDefinition>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
