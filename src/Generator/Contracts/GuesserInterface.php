<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Contracts;

interface GuesserInterface
{
    public function guess(): string;
}
