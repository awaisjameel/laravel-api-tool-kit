<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests\Analysis;

use Essa\APIToolKit\Traits\IncludeTranslation;
use Illuminate\Foundation\Http\FormRequest;

class RequestConsumer extends FormRequest
{
    use IncludeTranslation;
}
