<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests\Analysis;

use Essa\APIToolKit\Traits\HasPermissions;
use Illuminate\Routing\Controller;

class ControllerConsumer extends Controller
{
    use HasPermissions;
}
