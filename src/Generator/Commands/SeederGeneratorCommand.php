<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Commands;

use Essa\APIToolKit\Enum\GeneratorFilesType;

class SeederGeneratorCommand extends GeneratorCommand
{
    protected GeneratorFilesType $type = GeneratorFilesType::SEEDER;

    protected function getStubName(): string
    {
        return 'DummySeeder';
    }
}
