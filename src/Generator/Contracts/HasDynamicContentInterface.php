<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Contracts;

interface HasDynamicContentInterface
{
    /**
     * @return array<string, string>
     */
    public function getContent(): array;
}
