<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Commands;

use Essa\APIToolKit\Enum\GeneratorFilesType;
use Essa\APIToolKit\Generator\Contracts\HasDynamicContentInterface;
use Essa\APIToolKit\Generator\SchemaParsers\FactoryColumnsParser;

class FactoryGeneratorCommand extends GeneratorCommand implements HasDynamicContentInterface
{
    protected GeneratorFilesType $type = GeneratorFilesType::FACTORY;

    /**
     * @return array<string, string>
     */
    public function getContent(): array
    {
        $namespace = $this->modelNamespace();
        return [
            '{{factoryContent}}' => (new FactoryColumnsParser($this->apiGenerationCommandInputs->getSchema(), $namespace))->parse(),
        ];
    }

    protected function getStubName(): string
    {
        return 'DummyFactory';
    }
}
