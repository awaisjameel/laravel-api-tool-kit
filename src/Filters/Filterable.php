<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Filters;

use Essa\APIToolKit\Filters\DTO\FiltersDTO;
use Essa\APIToolKit\Filters\Exceptions\MissingDefaultFiltersException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 * @method static Builder<static> useFilters(string $filterClass = null, ?FiltersDTO $filteredDTO = null) filter class
 */
trait Filterable
{
    /** @param Builder<\Illuminate\Database\Eloquent\Model> $query
     * @return Builder<\Illuminate\Database\Eloquent\Model> */
    public function scopeUseFilters(
        Builder $query,
        ?string $filterClass = null,
        ?FiltersDTO $filteredDTO = null
    ): Builder {
        if (! property_exists($this, 'default_filters') && null === $filterClass) {
            throw new MissingDefaultFiltersException();
        }

        $filteredDTO ??= FiltersDTO::buildFromRequest(app(Request::class));

        $class = app($filterClass ?? $this->default_filters);
        if (!$class instanceof QueryFilters) {
            throw new \InvalidArgumentException('The filter class must extend QueryFilters.');
        }

        return $class->setFiltersDTO($filteredDTO)
            ->setBuilder($query)
            ->apply();
    }
}
