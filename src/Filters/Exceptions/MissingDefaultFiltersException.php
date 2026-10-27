<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Filters\Exceptions;

use Exception;

class MissingDefaultFiltersException extends Exception
{
    protected $message = 'Please add the default_filters property to the model.';
}
