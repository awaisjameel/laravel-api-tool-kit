<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Filters\DTO;

use Illuminate\Database\Eloquent\Builder;

class QueryFiltersOptionsDTO
{
    /**
     * @param Builder<\Illuminate\Database\Eloquent\Model> $builder
     * @param list<string> $allowedFilters
     * @param list<string> $allowedSorts
     * @param list<string> $allowedIncludes
     * @param list<string> $columnSearch
     * @param array<string, list<string>> $relationSearch
     */
    public function __construct(
        private Builder $builder,
        private FiltersDTO $filtersDTO,
        private array $allowedFilters = [],
        private array $allowedSorts = [],
        private array $allowedIncludes = [],
        private array $columnSearch = [],
        private array $relationSearch = []
    ) {
    }

    /**
     * @return list<string>
     */
    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }

    /**
     * @return list<string>
     */
    public function getAllowedSorts(): array
    {
        return $this->allowedSorts;
    }

    /**
     * @return list<string>
     */
    public function getAllowedIncludes(): array
    {
        return $this->allowedIncludes;
    }

    /**
     * @return list<string>
     */
    public function getColumnSearch(): array
    {
        return $this->columnSearch;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getRelationSearch(): array
    {
        return $this->relationSearch;
    }

    /**
     * @param list<string> $allowedFilters
     */
    public function setAllowedFilters(array $allowedFilters): self
    {
        $this->allowedFilters = $allowedFilters;

        return $this;
    }

    /**
     * @param list<string> $allowedSorts
     */
    public function setAllowedSorts(array $allowedSorts): self
    {
        $this->allowedSorts = $allowedSorts;

        return $this;
    }

    /**
     * @param list<string> $allowedIncludes
     */
    public function setAllowedIncludes(array $allowedIncludes): self
    {
        $this->allowedIncludes = $allowedIncludes;

        return $this;
    }

    /**
     * @param list<string> $columnSearch
     */
    public function setColumnSearch(array $columnSearch): self
    {
        $this->columnSearch = $columnSearch;

        return $this;
    }

    /**
     * @param array<string, list<string>> $relationSearch
     */
    public function setRelationSearch(array $relationSearch): self
    {
        $this->relationSearch = $relationSearch;

        return $this;
    }

    /**
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * @param Builder<\Illuminate\Database\Eloquent\Model> $builder
     */
    public function setBuilder(Builder $builder): self
    {
        $this->builder = $builder;

        return $this;
    }

    public function getFiltersDTO(): FiltersDTO
    {
        return $this->filtersDTO;
    }

    public function setFiltersDTO(FiltersDTO $filtersDTO): self
    {
        $this->filtersDTO = $filtersDTO;

        return $this;
    }
}
