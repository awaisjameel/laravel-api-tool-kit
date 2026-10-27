<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Commands;

use Essa\APIToolKit\Enum\GeneratorFilesType;
use Essa\APIToolKit\Generator\Contracts\HasDynamicContentInterface;
use Essa\APIToolKit\Generator\SchemaParsers\RelationshipMethodsParser;

class ModelGeneratorCommand extends GeneratorCommand implements HasDynamicContentInterface
{
    protected GeneratorFilesType $type = GeneratorFilesType::MODEL;

    /**
     * @return array<string, string>
     */
    public function getContent(): array
    {
        return [
            '{{modelRelations}}' => (new RelationshipMethodsParser($this->apiGenerationCommandInputs->getSchema(), $this->modelNamespace()))->parse(),
        ];
    }

    protected function getStubName(): string
    {
        return 'DummyModel';
    }
}
