# Models

## Fat Models — What Belongs Here

Models SHOULD contain logic about their own data (fat model = good):
- Boolean checks: `$order->isApproved()`, `$user->hasReachedLimit()`
- Calculations from own fields: `$order->getTotalWithTax()`
- Single-step state changes: `$car->toggleActivation()`
- Scopes, accessors, mutators, relationships

Models MUST NOT contain (god object = bad):
- External service calls (emails, file uploads, API calls) — use Actions
- Multi-step workflows with side effects — use Actions
- Operations spanning multiple unrelated models — use Actions

## Rules

- Preserve this fork's `protected $guarded = []` model policy. Persist validated, explicitly selected input; never pass raw request data to mass assignment. Do not replace it with a `$fillable` allowlist unless the application owner changes that policy.
- MUST add `$casts` for all booleans, enums, arrays, and dates
- MUST use `Filterable` trait and bind `$default_filters` for any model with a filterable endpoint
- NEVER create a scope for a simple single-column condition — use `where()` directly
- Scopes MUST accept an explicit `$userId` parameter — NEVER hardcode `auth()->id()` in a scope (breaks in queues, caller is always responsible for passing the ID)
- Enable `Model::shouldBeStrict(! app()->isProduction())` in `AppServiceProvider::boot()`

## Structure

```php
class Car extends Model
{
    use HasFactory, Filterable;

    protected string $default_filters = CarFilters::class;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'status'    => CarStatusEnum::class,
    ];
}
```

## ULID Primary Keys

When the project uses ULID as the primary key, use the `HasUlids` trait — it auto-generates IDs and sets the correct key type. Also explicitly declare `$keyType` and `$incrementing` so Eloquent comparisons and route model binding work correctly:

```php
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Car extends Model
{
    use HasFactory, Filterable, HasUlids;

    protected $keyType = 'string';
    public $incrementing = false;

    // ...
}
```

Enable all three strict-mode guards in `AppServiceProvider::boot()`. Read from config — never call `env()` directly in application code:

```php
// config/app.php — add this key
'strict' => (bool) env('APP_STRICT', true),

// AppServiceProvider::boot()
$strict = config('app.strict');

Model::shouldBeStrict($strict);
Model::preventLazyLoading($strict);
Model::preventSilentlyDiscardingAttributes($strict);
```

Set `APP_STRICT=true` in `.env.example` so all developers get strict mode locally. Set `APP_STRICT=false` in the production `.env`.

## Model Method (single-step, own data)

```php
public function toggleActivation(): void
{
    $this->update(['is_active' => ! $this->is_active]);
}

public function isOwnedBy(User $user): bool
{
    return $this->user_id === $user->id;
}
```

When the operation has multiple steps or side effects — move it to an Action. See `rules/actions.md`.

## Scope Pattern

```php
// Wrong — hardcoded auth, breaks in queues
public function scopeForCurrentUser(Builder $q): Builder
{
    return $q->where('user_id', auth()->id());
}

// Wrong — still calls auth()->id() when $userId is null (queue case)
public function scopeForUser(Builder $q, ?string $userId = null): Builder
{
    return $q->where('user_id', $userId ?? auth()->id());
}

// Correct — caller is always responsible for passing the user ID
public function scopeForUser(Builder $q, string $userId): Builder
{
    return $q->where('user_id', $userId);
}
```

## Package Traits

| Trait | Adds |
|-------|------|
| `Filterable` | `useFilters()` scope |
| `HasActivation` | `is_active`, `activate()`, `deactivate()` |
| `HasCreatedBy` | `created_by` auto-fill from auth |
| `HasGeneratedCode` | auto-generated unique `code` column |
| `HasOrder` | `order` column with ordering helpers |
| `Sluggable` | auto-generated `slug` |

Import from `Essa\APIToolKit\Traits\{TraitName}`.
