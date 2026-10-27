<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests;

use Essa\APIToolKit\APIToolKit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionIntegrationTest extends TestCase
{
    #[Test]
    public function preparedModelExceptionsAndHttpHeadersAreRenderedThroughTheRealKernel(): void
    {
        APIToolKit::registerExceptionRenderers(new Exceptions(app(ExceptionHandler::class)));
        Route::get('/model-error', fn () => throw (new ModelNotFoundException())->setModel('App\\Models\\Widget', [42]));
        $this->getJson('/model-error')->assertNotFound()->assertJsonPath('errors.0.detail', 'Widget with id 42 not found');
        Route::get('/limited', fn () => throw new HttpException(429, 'limited', headers: ['Retry-After' => '60']));
        $this->getJson('/limited')->assertStatus(429)->assertHeader('Retry-After', '60')->assertJsonPath('errors.0.status', 429);
    }

    #[Test]
    public function databaseErrorsAreServerErrorsAndDoNotExposeSqlInProduction(): void
    {
        APIToolKit::registerExceptionRenderers(new Exceptions(app(ExceptionHandler::class)));
        $this->app->instance('env', 'production');
        config()->set('app.debug', true);
        Route::get('/database-error', fn () => DB::select('select private_column from non_existing_table'));
        $response = $this->getJson('/database-error')->assertStatus(500)->assertJsonPath('errors.0.status', 500);
        self::assertStringNotContainsString('private_column', $response->getContent());
    }

    #[Test]
    #[DataProvider('serverErrorEnvironments')]
    public function serverHttpErrorDetailsRespectTheEnvironment(string $environment, bool $debug, bool $expose): void
    {
        APIToolKit::registerExceptionRenderers(new Exceptions(app(ExceptionHandler::class)));
        $this->app->instance('env', $environment);
        config()->set('app.debug', $debug);
        Route::get('/server-error', fn () => throw new HttpException(503, 'private-upstream-detail', headers: ['Retry-After' => '30']));
        $response = $this->getJson('/server-error')->assertStatus(503)->assertHeader('Retry-After', '30')->assertJsonPath('errors.0.status', 503);
        if ($expose) {
            self::assertStringContainsString('private-upstream-detail', $response->getContent());
        } else {
            self::assertStringNotContainsString('private-upstream-detail', $response->getContent());
        }
    }

    /** @return iterable<string, array{string, bool, bool}> */
    public static function serverErrorEnvironments(): iterable
    {
        yield 'production debug disabled' => ['production', false, false];
        yield 'production debug enabled' => ['production', true, false];
        yield 'local debug disabled' => ['local', false, false];
        yield 'local debug enabled' => ['local', true, true];
    }
}
