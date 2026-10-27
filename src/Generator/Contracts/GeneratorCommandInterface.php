<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Contracts;

use Essa\APIToolKit\Generator\ApiGenerationCommandInputs;

interface GeneratorCommandInterface
{
    public function run(ApiGenerationCommandInputs $apiGenerationCommandInputs): void;
}
