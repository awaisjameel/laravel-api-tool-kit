<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests\Analysis;

use Essa\APIToolKit\Api\ConsumesExternalServices;
use Illuminate\Http\Client\PendingRequest;

class ServiceConsumer
{
    use ConsumesExternalServices;
    protected ?int $timeout = null;
    protected ?int $connectTimeout = null;
    public function __construct(protected string $baseUri)
    {
    }
    protected function resolveAuthorization(PendingRequest $request): PendingRequest
    {
        return $request->withToken('fixture-token');
    }
}
