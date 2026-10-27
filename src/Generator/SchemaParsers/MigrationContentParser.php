<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\SchemaParsers;

use Essa\APIToolKit\Generator\ColumnDefinition;
use Essa\APIToolKit\Generator\Helpers\PhpLiteral;
use Essa\APIToolKit\Generator\ModelNaming;
use Essa\APIToolKit\Generator\SchemaDefinition;
use Illuminate\Support\Str;

class MigrationContentParser extends SchemaParser
{
    protected function getParsedSchema(SchemaDefinition $schemaDefinition): string
    {
        return collect($schemaDefinition->getColumns())
            ->map(fn (ColumnDefinition $definition): string => $this->generateColumnDefinition($definition))
            ->implode(PHP_EOL . "\t\t\t");
    }

    private function generateColumnDefinition(ColumnDefinition $definition): string
    {
        if ($definition->isForeignKey()) {
            $columnOptions = [];
            $foreignOptions = [];
            foreach ($definition->getOptions() as $option) {
                $method = explode('(', $option, 2)[0];
                if (in_array($method, ['cascadeOnDelete', 'cascadeOnUpdate', 'nullOnDelete', 'nullOnUpdate', 'restrictOnDelete', 'restrictOnUpdate', 'noActionOnDelete', 'noActionOnUpdate', 'onDelete', 'onUpdate', 'references', 'on', 'deferrable', 'initiallyImmediate'], true)) {
                    $foreignOptions[] = $option;
                } else {
                    $columnOptions[] = $option;
                }
            }
            $table = ModelNaming::tableName(Str::studly(Str::beforeLast($definition->getName(), '_id')));
            return "\$table->foreignId('{$definition->getName()}')" . $this->getOptionString($columnOptions) . "->constrained('{$table}')" . $this->getOptionString($foreignOptions) . ';';
        }
        $columnDefinition = $this->getColumnDefinition($definition);
        $optionsString = $this->getOptionString($definition->getOptions());

        return $columnDefinition . $optionsString . ';';
    }

    private function getColumnDefinition(ColumnDefinition $definition): string
    {
        if ($definition->isEnumType()) {
            return $this->getEnumColumnDefinition($definition);
        }

        $arguments = $definition->getTypeArguments();
        $extra = $arguments !== [] ? ', ' . implode(', ', $arguments) : '';
        return "\$table->{$definition->getType()}('{$definition->getName()}'{$extra})";
    }

    /**
     * @param list<string> $options
     */
    private function getOptionString(array $options): string
    {
        return collect($options)
            ->map(fn ($option) => $this->addOption($option))
            ->implode('');
    }

    private function addOption(string $option): string
    {
        return preg_match('/\(/', $option) ? "->{$option}" : "->{$option}()";
    }

    private function getEnumColumnDefinition(ColumnDefinition $definition): string
    {
        $enumValuesString = PhpLiteral::stringList($definition->getEnumValues());

        return "\$table->enum('{$definition->getName()}', [{$enumValuesString}])";
    }
}
