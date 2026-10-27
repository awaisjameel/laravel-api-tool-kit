<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Exception;

use Exception;

class SchemaNotValidException extends Exception
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('your schema is not valid', 0, $previous);
    }
}
