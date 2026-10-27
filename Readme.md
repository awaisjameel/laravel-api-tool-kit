# Laravel API Tool Kit - maintained fork

Version 4 modernizes Ahmed Esa's API Toolkit for PHP 8.2-8.5 and Laravel 12/13. Laravel 13 needs PHP 8.3+. It includes upstream commits through `79793b2` and preserves this fork's legacy enum casting, snake-case table names, kebab-case routes, and unguarded model scaffolding.

The Composer identity remains **`essa/api_tool_kit`**. A fork release must be available through your configured Composer repository; the upstream Packagist entry does not automatically serve fork tags. After a 4.x fork tag is published, a VCS setup can use:

```json
{
    "repositories": [{"type": "vcs", "url": "https://github.com/awais-afoxtech/laravel-api-tool-kit"}],
    "require": {"essa/api_tool_kit": "^4.0"}
}
```

See [MIGRATION.md](MIGRATION.md) for breaking changes and [CHANGELOG.md](CHANGELOG.md) for the prepared release.

## Setup and responses

Laravel discovers both service providers. Publish configuration/stubs when customization is needed:

```sh
php artisan vendor:publish --provider="Essa\APIToolKit\APIToolKitServiceProvider" --tag=config
php artisan vendor:publish --provider="Essa\APIToolKit\APIToolKitServiceProvider" --tag=stubs
```

Use `Essa\APIToolKit\Api\ApiResponse` in application controllers. Generated controllers include it directly. Success responses contain `status`, `message`, and `data`; errors use an `errors` array with status/title/detail and `application/problem+json`. Existing response method names remain available, including `responseSuccess`, `responseCreated`, `responseAccepted`, `responseDeleted`, `responseNoContent`, and the error helpers. Deletion/no-content responses have HTTP 204.

Register exception rendering in `bootstrap/app.php`:

```php
use Essa\APIToolKit\APIToolKit;
use Illuminate\Foundation\Configuration\Exceptions;

// Application::configure(...)
    ->withExceptions(function (Exceptions $exceptions): void {
        APIToolKit::registerExceptionRenderers($exceptions);
    });
```

JSON requests get the package envelope for authentication, validation, HTTP and database errors. Prepared Laravel model/authorization exceptions retain their HTTP status, and HTTP headers such as `Retry-After` survive rendering. Database errors return 500; SQL details are included only with debug enabled outside production. Non-JSON requests retain Laravel's normal rendering.

## Generate a resource

```sh
php artisan api:generate BlogPost "title:string|amount:decimal(10,2)|published_at:datetime:nullable" --all --soft-delete
```

The generator creates configured models, migrations, controllers, requests, resources, factories, seeders, filters, routes and test scaffolds. Names follow `blog_posts` for tables and `/blog-posts` for routes. Configure `groups_files_paths`, namespaces and `groups_url_prefixes` for alternative structures; factories/relations use the configured model namespace. Register the generated routes in the host application, for example through Laravel's `withRouting(api: ...)`. The configured URL prefix describes that application route group; it does not prepend itself inside the generated file.

Schema fields use `name:type:modifier:modifier|...`. Names are simple PHP identifiers. Types use Laravel Blueprint method names; `int`, `varchar`, and `datetime` normalize to `integer`, `string`, and `dateTime`. Numeric type arguments such as `string(80)` and `decimal(10,2)` are supported. Simple `enum(draft,published)` values contain letters, digits, underscores or hyphens; optional matching quotes are removed. Embedded quotes and other enum punctuation are rejected before writing because Blueprint embeds these values directly in migration SQL. For richer values, write an application migration and validation rule explicitly. Modifiers use Blueprint syntax, such as `nullable`, `unique`, or `default('draft')`. Quoted values cannot contain the schema's `:` / `|` separators. Review database-specific types, lengths/defaults and constraints before running generated migrations.

Existing selected source files are preserved by default. Use **`--force`** only after reviewing the files to regenerate. Rendered PHP is syntax-checked before writing. Writes are atomic per file; failed runs attempt to restore planned files from their snapshots and report restoration failures. Managed route blocks are replaced rather than appended twice, and a single existing create-table migration is reused. Multiple matching create migrations fail clearly. Already-run migrations should normally be evolved with a new migration rather than overwritten. Serialize generator invocations for a project; custom generator commands must manage any side effects outside their configured output files.

Generated models intentionally retain **`protected $guarded = []`**, the fork's empty `casts()`, `$with`, `$appends`, and `$translatable` scaffolding. Controllers persist validated data, including when `--request` is omitted. Review schema rules, authentication, authorization, sensitive fields, relationship conventions, factories, and the deliberately incomplete test scaffolds before exposing an application endpoint. Generation does not define application-specific access policy.

Soft-delete restoration uses **POST** `/{id}/restore`; permanent deletion uses **DELETE** `/{id}/permanent-delete`. GET does not restore records. Generated resource dates accept immutable/mutable date objects as well as strings.

`make:action` and `make:filter` generate standalone scaffolds. Use Laravel's native `make:enum Enums/Status --string` for new backed enums. Generated factories/seeders require `fakerphp/faker` in the application's development environment.

## Filtering and pagination

```php
use Essa\APIToolKit\Filters\QueryFilters;

class BlogPostFilters extends QueryFilters
{
    protected array $allowedFilters = ['state'];
    protected array $allowedSorts = ['title', 'created_at'];
    protected array $allowedIncludes = ['author'];
    protected array $columnSearch = ['title'];
    protected array $relationSearch = ['author' => ['name']];

    public function year(string $term): void
    {
        $this->builder->whereYear('created_at', $term);
    }
}
```

Use `Filterable` on a model and set `protected string $default_filters = BlogPostFilters::class`, or pass a filter class/`FiltersDTO` explicitly to `useFilters()`. Generated filters start with empty allowlists so the application chooses exposed columns and relationships.

Request options include `?state=draft,published`, `?sorts=-created_at`, `?includes=author`, and `?search=term`. The first allowed sort is applied. Custom filters must be public, non-static, accept exactly one argument, return `void`, and be declared outside the base `QueryFilters` infrastructure. Keep helper methods protected/private. Query keys cannot invoke base setters or recursively call `apply()`. Malformed array-valued request parameters return validation errors. `DateFilter` / `TimeFilter` add the existing date/time range filters.

```php
BlogPost::useFilters()->dynamicPaginate();
```

`per_page` falls back to the configured positive default for malformed/non-positive values and respects `max_pagination_limit`. Pagination links preserve query parameters. `pagination=none` returns the collection without pagination; applications own authorization and result-size limits for that mode.

## Enums, model traits and helpers

Use `Essa\APIToolKit\Enum\EnumHelpers` in string- or integer-backed enums. `values()`, `names()`, `toArray()`, `isValid()` and `fromValue()` use strict backing-value types; integer `1` and string `"1"` differ.

The fork's `Essa\APIToolKit\Enum\Enum` constant class remains available for existing models. Its Eloquent cast accepts a declared constant name or a strict declared value, stores the underlying scalar and returns the stored value. Native Laravel backed-enum casts return enum instances. These are intentionally different APIs; no forced conversion of existing data occurs.

Model helpers include activation, generated codes, slugs, cache invalidation and ordering. `HasCreatedBy` uses the configured Eloquent user model. `HasOrder::sort()` runs in a transaction and fails on missing records rather than partially reordering. `HasPermissions` expects a controller supporting Laravel's instance `middleware()` API; an application using a plain base controller must extend `Illuminate\Routing\Controller` or integrate middleware through its own architecture. Permission middleware itself belongs to the application.

`generate:permissions posts` writes a mapping and deduplicated CSV, preserving other groups and working without a missing template. Enable `api-tool-kit.use_soft_delete` for `restore` / `force-delete` entries. Register that middleware and provision permissions in your own authorization system.

`dateTimeFormat()` accepts `DateTimeInterface`, strings and null. `createOrRandomFactory()` returns one existing model or creates one with the model's factory; factories are an application development dependency.

## Media and external services

```php
use Essa\APIToolKit\MediaHelper;

$images = MediaHelper::disk('public');
$path = $images->uploadFile($request->file('image'), 'images');
```

Each `disk()` selection is an independent `MediaStorage` instance. Static `MediaHelper` calls use Laravel's default disk, and no selection leaks to another call. Uploads reject unsafe file-name path components and report storage failures. Base64 uploads require a valid raster-image data URI whose decoded image header matches its MIME label; generated extensions reflect the actual image type. SVG and malformed/non-image data are rejected. Protect application upload sizes and access rules separately.

`ConsumesExternalServices::makeRequest($method, $url, $data, $headers, $isJsonRequest)` uses Laravel's HTTP client. Provide `$baseUri`, optional timeout/connect-timeout and opt-in retry properties. Override `resolveAuthorization(PendingRequest): PendingRequest` and `handleRequestError(Response)` as needed. Form encoding is honored, JSON remains the response format, failed responses throw by default, and redirects are rejected rather than forwarding credentials. Configure retries deliberately for non-idempotent operations.

## Optional AI rules/workflows

```sh
php artisan api-skill:install claude
php artisan api-skill:install cursor
php artisan api-skill:install copilot
php artisan api-skill:install antigravity
```

The archive includes 22 rule files and 9 workflows. The installer produces native tool files and preserves unrelated instruction text. Existing unmarked workflow/knowledge files are retained unless `--force` is explicit. Managed marker sections update literally and idempotently; generated skill descriptions use valid quoted YAML. Review project defaults such as primary keys and guards before applying these optional application-development rules. Installing these files does not run their workflows.

## Development and validation

```sh
composer install
composer test
composer check-code
composer test-coverage
```

The tracked lock records the primary PHP 8.5 / Laravel 13 development environment.
When developing on an older supported PHP branch, run `composer update` to resolve
its compatible PHPUnit/Testbench versions; CI resolves each supported pair separately.

Tests cover current PHPUnit attributes, generators and actual migrations, enum casting, filters, media isolation/content, external-service request encoding, exception responses, permission/skill installers and file protection. PHPStan level 8 also analyzes concrete consumers of reusable traits, without an error baseline. CI covers maintained PHP/Laravel combinations on Linux and Windows, with stable/minimum dependencies. Release acceptance additionally installs real Composer archives in no-dev Laravel 12/13 consumers and executes generated CRUD and cached routes. Live hosting and the application's production database/authorization integrations remain application acceptance work.

Upstream authorship and the [MIT license](LICENSE.md) are preserved. See [CONTRIBUTING.md](CONTRIBUTING.md).
