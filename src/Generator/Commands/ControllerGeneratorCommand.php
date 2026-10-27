<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Commands;

use Essa\APIToolKit\Enum\GeneratorFilesType;
use Essa\APIToolKit\Generator\Contracts\HasDynamicContentInterface;
use Essa\APIToolKit\Generator\SchemaParsers\CreateValidationRulesParser;
use Essa\APIToolKit\Generator\SchemaParsers\UpdateValidationRulesParser;

class ControllerGeneratorCommand extends GeneratorCommand implements HasDynamicContentInterface
{
    /** @return array<string, string> */
    public function getContent(): array
    {
        $schema = $this->apiGenerationCommandInputs->getSchema();
        return ['{{createValidationRules}}' => (new CreateValidationRulesParser($schema))->parse(), '{{updateValidationRules}}' => (new UpdateValidationRulesParser($schema))->parse()];
    }
    protected GeneratorFilesType $type = GeneratorFilesType::CONTROLLER;

    protected function getStubName(): string
    {
        return 'DummyController';
    }
}
