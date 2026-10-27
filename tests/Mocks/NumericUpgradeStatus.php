<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests\Mocks;

use Essa\APIToolKit\Enum\EnumHelpers;

enum NumericUpgradeStatus: int
{
    use EnumHelpers;
    case Active = 1;
}
