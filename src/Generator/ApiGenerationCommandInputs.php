<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator;

class ApiGenerationCommandInputs
{
    /**
     * @param array<string, bool> $userChoices
     */
    public function __construct(
        private string $model,
        private array $userChoices,
        private SchemaDefinition $schema,
        private string $pathGroup
    ) {
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @return array<string, bool>
     */
    public function getUserChoices(): array
    {
        return $this->userChoices;
    }

    public function getSchema(): SchemaDefinition
    {
        return $this->schema;
    }

    public function isOptionSelected(string $option): bool
    {
        return $this->userChoices[$option] ?? false;
    }

    public function getPathGroup(): string
    {
        return $this->pathGroup;
    }

    public function setPathGroup(string $pathGroup): void
    {
        $this->pathGroup = $pathGroup;
    }
}
