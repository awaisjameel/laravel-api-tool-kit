<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Filters\DTO;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FiltersDTO
{
    /** @var array<string, string|null> */
    private array $filters = [];
    /** @var list<string> */
    private array $includes = [];

    /**
     * @param array<array-key, mixed>|null $filters
     * @param array<array-key, mixed>|null $includes
     */
    public function __construct(private ?string $sorts = null, ?array $filters = null, ?array $includes = null, private ?string $search = null)
    {
        $this->setFilters($filters);
        $this->setIncludes($includes);
    }

    public static function buildFromRequest(Request $request): self
    {
        $values = [];
        foreach (['sorts', 'includes', 'search'] as $key) {
            $value = $request->input($key);
            if ($value !== null && !is_string($value)) {
                throw ValidationException::withMessages([$key => 'The ' . $key . ' parameter must be a string.']);
            }
            $values[$key] = $value;
        }
        return new self($values['sorts'], $request->except('includes', 'sorts'), $values['includes'] !== null ? explode(',', $values['includes']) : [], $values['search']);
    }

    public function getSorts(): ?string
    {
        return $this->sorts;
    }
    public function setSorts(?string $sorts): void
    {
        $this->sorts = $sorts;
    }
    /** @return array<string, string|null> */
    public function getFilters(): array
    {
        return $this->filters;
    }
    /** @param array<string, scalar|null>|null $filters
     * @param array<array-key, mixed>|null $filters
     */
    public function setFilters(?array $filters): void
    {
        $normalized = [];
        foreach ($filters ?? [] as $key => $value) {
            if (!is_string($key) || (!is_scalar($value) && $value !== null)) {
                throw ValidationException::withMessages(['filters' => 'Filter names and values must be scalar parameters.']);
            }
            $normalized[$key] = $value === null ? null : (is_bool($value) ? ($value ? '1' : '0') : (string)$value);
        }
        $this->filters = $normalized;
    }
    /** @return list<string> */
    public function getIncludes(): array
    {
        return $this->includes;
    }
    /** @param list<string>|null $includes
     * @param array<array-key, mixed>|null $includes
     */
    public function setIncludes(?array $includes): void
    {
        $normalized = [];
        foreach ($includes ?? [] as $include) {
            if (!is_string($include)) {
                throw ValidationException::withMessages(['includes' => 'Includes must be relationship names.']);
            }
            if (trim($include) !== '') {
                $normalized[] = trim($include);
            }
        }
        $this->includes = array_values(array_unique($normalized));
    }
    public function getSearch(): ?string
    {
        return $this->search;
    }
    public function setSearch(?string $search): void
    {
        $this->search = $search;
    }
    public function getFilter(string $key): ?string
    {
        return $this->filters[$key] ?? null;
    }
}
