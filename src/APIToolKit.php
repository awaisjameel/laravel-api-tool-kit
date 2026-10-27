<?php

declare(strict_types=1);

namespace Essa\APIToolKit;

use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Register API renderers inside bootstrap/app.php's withExceptions callback. */
class APIToolKit
{
    use ApiResponse;

    final public function __construct()
    {
    }

    public static function registerExceptionRenderers(Exceptions $exceptions): void
    {
        $instance = new static();
        $exceptions->render(function (ValidationException $exception, Request $request) use ($instance): ?JsonResponse {
            return $request->expectsJson() ? $instance->ResponseValidationError($exception) : null;
        });
        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($instance): ?JsonResponse {
            return $request->expectsJson() ? $instance->responseUnAuthenticated($exception->getMessage()) : null;
        });
        $exceptions->render(function (QueryException $exception, Request $request) use ($instance): ?JsonResponse {
            if (!$request->expectsJson()) {
                return null;
            }
            return $instance->responseServerError($instance->shouldExposeErrorDetails() ? $exception->getMessage() : null);
        });
        // Laravel prepares ModelNotFoundException and AuthorizationException into HTTP
        // exceptions before calling render callbacks. Preserve their prepared status/headers.
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($instance): ?JsonResponse {
            if (!$request->expectsJson()) {
                return null;
            }
            $status = $exception->getStatusCode();
            $previous = $exception->getPrevious();
            $detail = $exception->getMessage();
            if ($previous instanceof ModelNotFoundException) {
                $ids = $previous->getIds();
                $detail = class_basename($previous->getModel()) . ($ids !== [] ? ' with id ' . implode(', ', $ids) : '') . ' not found';
            }
            $response = match ($status) {
                401 => $instance->responseUnAuthenticated($detail),
                403 => $instance->responseUnAuthorized(),
                404 => $instance->responseNotFound($detail),
                409 => $instance->responseConflictError($detail),
                422 => $instance->responseUnprocessable($detail),
                429 => $instance->responseWithCustomError('Too Many Attempts.', 'Too Many Attempts Please Try Again Later.', 429),
                default => $instance->responseWithCustomError(Response::$statusTexts[$status] ?? 'HTTP Error', $status >= 500 && !$instance->shouldExposeErrorDetails() ? null : $detail, $status),
            };
            return $response->withHeaders($exception->getHeaders());
        });
    }

    private function shouldExposeErrorDetails(): bool
    {
        return config('app.debug') === true && !app()->isProduction();
    }
}
