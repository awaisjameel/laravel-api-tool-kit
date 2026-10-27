<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests;

use Essa\APIToolKit\Tests\Analysis\ServiceConsumer;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;

class ExternalServiceTest extends TestCase
{
    #[Test]
    public function formEncodingAndPerServiceAuthenticationArePreserved(): void
    {
        Http::preventStrayRequests();
        Http::fake(['service.example/*' => Http::response(['accepted' => true])]);
        $service = new ServiceConsumer('https://service.example/v1');
        self::assertSame(['accepted' => true], $service->makeRequest('POST', '/messages', ['body' => 'space & plus+'], isJsonRequest:false));
        Http::assertSent(function ($request): bool {
            parse_str($request->body(), $form);
            return $request->hasHeader('Authorization', 'Bearer fixture-token') && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded') && ($form['body'] ?? null) === 'space & plus+';
        });
    }

    #[Test]
    public function failedResponsesThrowAndRedirectsCannotForwardCredentials(): void
    {
        Http::preventStrayRequests();
        Http::fake(['service.example/v1/failed' => Http::response(['error' => 'rejected'], 422),'service.example/v1/redirect' => Http::response('', 302, ['Location' => 'https://foreign.example/'])]);
        $service = new ServiceConsumer('https://service.example/v1');
        try {
            $service->makeRequest('GET', '/failed');
            self::fail('Expected HTTP failure.');
        } catch (RequestException $error) {
            self::assertSame(422, $error->response->status());
        }
        try {
            $service->makeRequest('GET', '/redirect');
            self::fail('Expected redirect rejection.');
        } catch (\UnexpectedValueException) {
            Http::assertNotSent(fn ($request) => str_contains($request->url(), 'foreign.example'));
        }
    }
}
