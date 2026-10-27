<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests\Analysis;

use Essa\APIToolKit\Filters\QueryFilters;
use Essa\APIToolKit\Traits\DateFilter;
use Essa\APIToolKit\Traits\TimeFilter;

class FiltersConsumer extends QueryFilters
{
    use DateFilter;
    use TimeFilter;
}
