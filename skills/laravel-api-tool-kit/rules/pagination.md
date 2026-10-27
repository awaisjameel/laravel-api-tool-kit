# Pagination

## Rules

- MUST use `dynamicPaginate()` on all list endpoints — NEVER `->paginate()` or `->get()`
- MUST call `useFilters()` before `dynamicPaginate()` on filterable models
- NEVER hardcode a page size in the controller

## Usage

```php
$cars = Car::useFilters()->dynamicPaginate();
$cars = Car::with(['brand'])->useFilters()->dynamicPaginate();
```

## Client Parameters

| Parameter | Example | Effect |
|-----------|---------|--------|
| `per_page` | `?per_page=25` | Items per page (capped at the package max limit) |
| `page` | `?page=2` | Page number |
| `pagination` | `?pagination=none` | Disable pagination, return all records |

The package enforces its configured positive maximum `per_page`. Larger values are capped, and malformed/non-positive sizes fall back to the configured positive default. Test the effective limit from configuration and inspect returned pagination metadata; an HTTP success alone does not prove the cap. Links retain query parameters. `pagination=none` is an unbounded collection mode whose authorization and result-size policy belong to the application.
