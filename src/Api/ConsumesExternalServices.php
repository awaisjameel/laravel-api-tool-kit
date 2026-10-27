<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Api;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

trait ConsumesExternalServices
{
    /**
     * Send a request to an external service.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $requestUrl The URL for the request
     * @param array<string, mixed> $data Data to send in the request
     * @param array<string, string|list<string>> $headers Headers to include
     * @param bool $isJsonRequest Whether to send as JSON
     *
     * @return mixed The response data (decoded JSON or raw body)
     */
    public function makeRequest(
        string $method,
        string $requestUrl,
        array $data = [],
        array $headers = [],
        bool $isJsonRequest = true
    ): mixed {
        $request = Http::baseUrl($this->baseUri)
            ->withHeaders($headers)
            ->withoutRedirecting()
            ->timeout($this->timeout ?? 30)
            ->connectTimeout($this->connectTimeout ?? 10);

        if (isset($this->retries)) {
            $request->retry($this->retries, $this->retryDelay ?? 100);
        }

        $request = $this->resolveAuthorization($request);

        $method = strtoupper($method);
        if (! in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], true)) {
            throw new \InvalidArgumentException('Unsupported HTTP method.');
        }
        if (! $isJsonRequest) {
            $request = $request->asForm();
        }
        $options = in_array($method, ['GET', 'HEAD'], true) ? ['query' => $data] : [$isJsonRequest ? 'json' : 'form_params' => $data];
        $response = $request->send($method, $requestUrl, $options);

        if (! $response->successful()) {
            return $this->handleRequestError($response);
        }

        return $response->json();
    }

    /**
     * Handle failed request.
     * Override this method to customize error handling.
     */
    protected function handleRequestError(Response $response): mixed
    {
        $response->throw();
        throw new \UnexpectedValueException('External service returned an unexpected HTTP status: ' . $response->status());
    }

    protected function resolveAuthorization(\Illuminate\Http\Client\PendingRequest $request): \Illuminate\Http\Client\PendingRequest
    {
        return $request;
    }
}
