<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Filters;

use Essa\APIToolKit\Filters\DTO\FiltersDTO;
use Essa\APIToolKit\Filters\DTO\QueryFiltersOptionsDTO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;

/**
 * Class QueryFilters
 *
 * @package Essa\APIToolKit\Filters
 */
class QueryFilters
{
    protected FiltersDTO $filtersDTO;

    /** @var Builder<\Illuminate\Database\Eloquent\Model> */
    protected Builder $builder;

    /** @var list<string> */
    protected array $allowedFilters = [];

    /** @var list<string> */
    protected array $allowedSorts = [];

    /** @var list<string> */
    protected array $allowedIncludes = [];

    /** @var list<string> */
    protected array $columnSearch = [];

    /** @var array<string, list<string>> */
    protected array $relationSearch = [];

    /**
     * QueryFilters constructor.
     *
     * @param Pipeline $pipeline The Laravel pipeline instance.
     */
    public function __construct(private Pipeline $pipeline)
    {
    }

    /**
     * Apply the query filters to the builder.
     *
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function apply(): Builder
    {
        $this->runBeforeMethod();

        $this->applyCustomFilters();

        return $this->processFiltersPipeline();
    }

    /**
     * Get the underlying builder.
     *
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * Set the underlying builder.
     *
     * @param Builder<\Illuminate\Database\Eloquent\Model> $builder
     * @return $this
     */
    public function setBuilder(Builder $builder): self
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * Get the filters DTO.
     *
     * @return FiltersDTO
     */
    public function getFiltersDTO(): FiltersDTO
    {
        return $this->filtersDTO;
    }

    /**
     * Set the filters DTO.
     *
     * @param  FiltersDTO  $filtersDTO
     * @return $this
     */
    public function setFiltersDTO(FiltersDTO $filtersDTO): self
    {
        $this->filtersDTO = $filtersDTO;

        return $this;
    }

    /**
     * Run any custom filter methods before applying filters.
     *
     * @return void
     */
    protected function runBeforeMethod(): void
    {
        if (method_exists($this, 'before')) {
            $this->before();
        }
    }

    /**
     * Apply custom filters based on filter names and values.
     *
     * @return void
     */
    protected function applyCustomFilters(): void
    {
        foreach ($this->getFiltersDTO()->getFilters() as $name => $value) {
            if (!method_exists($this, $name)) {
                continue;
            }
            $method = new \ReflectionMethod($this, $name);
            if (!$method->isPublic() || $method->isStatic() || $method->getDeclaringClass()->getName() === self::class
                || $method->getNumberOfParameters() !== 1 || $method->getReturnType()?->__toString() !== 'void') {
                continue;
            }
            $this->{$name}($value);
        }
    }

    /**
     * Process the filters pipeline.
     *
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function processFiltersPipeline(): Builder
    {
        $options = new QueryFiltersOptionsDTO(
            builder: $this->builder,
            filtersDTO: $this->filtersDTO,
            allowedFilters: $this->allowedFilters,
            allowedSorts: $this->allowedSorts,
            allowedIncludes: $this->allowedIncludes,
            columnSearch: $this->columnSearch,
            relationSearch: $this->relationSearch
        );

        return $this->pipeline
            ->send($options)
            ->through(config('api-tool-kit-internal.filters.handlers'))
            ->thenReturn()
            ->getBuilder();
    }
}
