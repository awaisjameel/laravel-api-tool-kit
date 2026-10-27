<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Commands;

use Essa\APIToolKit\Enum\GeneratorFilesType;
use Essa\APIToolKit\Generator\Contracts\HasDynamicContentInterface;
use Essa\APIToolKit\Generator\SchemaParsers\MigrationContentParser;

class MigrationGeneratorCommand extends GeneratorCommand implements HasDynamicContentInterface
{
    protected GeneratorFilesType $type = GeneratorFilesType::MIGRATION;

    /**
     * @return array<string, string>
     */
    public function getContent(): array
    {
        return [
            '{{migrationContent}}' => (new MigrationContentParser($this->apiGenerationCommandInputs->getSchema()))->parse(),
        ];
    }

    protected function getStubName(): string
    {
        return 'DummyMigration';
    }
}
