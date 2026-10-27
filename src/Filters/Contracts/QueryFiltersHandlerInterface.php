<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Filters\Contracts;

use Closure;
use Essa\APIToolKit\Filters\DTO\QueryFiltersOptionsDTO;

interface QueryFiltersHandlerInterface
{
    public function handle(QueryFiltersOptionsDTO $queryFiltersOptionsDTO, Closure $next): QueryFiltersOptionsDTO;
}
