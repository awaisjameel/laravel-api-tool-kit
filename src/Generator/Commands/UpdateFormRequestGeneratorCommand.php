<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Commands;

use Essa\APIToolKit\Enum\GeneratorFilesType;
use Essa\APIToolKit\Generator\Contracts\HasDynamicContentInterface;
use Essa\APIToolKit\Generator\SchemaParsers\UpdateValidationRulesParser;

class UpdateFormRequestGeneratorCommand extends GeneratorCommand implements HasDynamicContentInterface
{
    protected GeneratorFilesType $type = GeneratorFilesType::UPDATE_REQUEST;

    /**
     * @return array<string, string>
     */
    public function getContent(): array
    {
        return [
            '{{updateValidationRules}}' => (new UpdateValidationRulesParser($this->apiGenerationCommandInputs->getSchema()))->parse(),
        ];
    }

    protected function getStubName(): string
    {
        return 'UpdateDummyRequest';
    }
}
