# Controllers

Controllers receive HTTP requests, delegate to the appropriate layer, and return responses. They contain NO business logic.

## Rules

- MUST extend the app's base `Controller`
- MUST use `$request->validated()` — NEVER `$request->all()` or `$request->except()`
- MUST use `ApiResponse` trait methods — NEVER `response()->json()` directly
- MUST wrap all model data in a Resource before returning
- MUST use `$this->authorize()` before any write operation — NEVER inline auth checks
- MUST use `trans()` for all user-facing message strings — NEVER hardcode strings
- MUST use `responseCreated()` for store, `responseDeleted()` for destroy
- NEVER write query logic in a controller — use filters or scopes
- NEVER write business logic in a controller — use Actions or model methods

## Standard CRUD

These application examples assume the base controller includes `Illuminate\Foundation\Auth\Access\AuthorizesRequests` and `Essa\APIToolKit\Api\ApiResponse`. Laravel's plain application controller does not provide these helpers automatically. Generated package controllers include `ApiResponse` themselves; add authorization policies deliberately.

```php
class CarController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CarResource::collection(Car::useFilters()->dynamicPaginate());
    }

    public function store(CreateCarRequest $request): JsonResponse
    {
        $this->authorize('create', Car::class);
        $car = Car::create($request->validated());
        return $this->responseCreated(trans('car.created'), new CarResource($car));
    }

    public function show(Car $car): JsonResponse
    {
        return $this->responseSuccess(null, new CarResource($car));
    }

    public function update(UpdateCarRequest $request, Car $car): JsonResponse
    {
        $this->authorize('update', $car);
        $car->update($request->validated());
        return $this->responseSuccess(trans('car.updated'), new CarResource($car));
    }

    public function destroy(Car $car): JsonResponse
    {
        $this->authorize('delete', $car);
        $car->delete();
        return $this->responseDeleted();
    }
}
```

## With an Injected Action

```php
class CarController extends Controller
{
    public function __construct(
        private readonly CreateCarAction $createCar,
    ) {}

    public function store(CreateCarRequest $request): JsonResponse
    {
        $car = $this->createCar->execute($request->validated());
        return $this->responseCreated(trans('car.created'), new CarResource($car));
    }
}
```

## Middleware

Apply middleware at the route level, or implement Laravel's `Illuminate\Routing\Controllers\HasMiddleware` interface with its static `middleware()` method. Instance constructor middleware also remains supported when the base extends `Illuminate\Routing\Controller`; it is not provided by a plain application controller. The package's existing `HasPermissions` trait uses that instance API. Choose one controller architecture consistently.

```php
// routes/api.php
Route::apiResource('cars', CarController::class)
    ->middleware('auth:sanctum');

Route::post('cars', [CarController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:10,1']);
```
