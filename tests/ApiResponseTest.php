<?php

namespace Essa\APIToolKit\Tests;

use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseTest extends TestCase
{
    private $classThatImplementTheTrait;

    public function setUp(): void
    {
        $this->classThatImplementTheTrait = new class () {
            use ApiResponse;
        };

        parent::setUp();
    }

    #[Test]
    public function responseServerError(): void
    {
        $response = $this->classThatImplementTheTrait->responseServerError('Server error details', 'Server error message');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    #[Test]
    public function responseSuccess(): void
    {
        $response = $this->classThatImplementTheTrait->responseSuccess('Success message', ['data' => 'value']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $response->getData();
        $this->assertEquals('Success message', $responseData->message);
        $this->assertEquals(['data' => 'value'], (array) $responseData->data);
    }

    #[Test]
    public function responseUnprocessable(): void
    {
        $response = $this->classThatImplementTheTrait->responseUnprocessable('Validation failed', 'Unprocessable entity');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $responseData = $response->getData();
        $this->assertEquals('Validation failed', $responseData->errors[0]->detail);
    }

    #[Test]
    public function responseBadRequest(): void
    {
        $response = $this->classThatImplementTheTrait->responseBadRequest('Invalid request', 'Bad request');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $response->getData();
        $this->assertEquals('Invalid request', $responseData->errors[0]->detail);
    }

    #[Test]
    public function responseDeleted(): void
    {
        $response = $this->classThatImplementTheTrait->responseDeleted();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('{}', $response->getContent());
    }

    #[Test]
    public function responseNotFound(): void
    {
        $response = $this->classThatImplementTheTrait->responseNotFound('Resource not found', 'Not found');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $responseData = $response->getData();
        $this->assertEquals('Resource not found', $responseData->errors[0]->detail);
    }

    #[Test]
    public function responseUnAuthorized(): void
    {
        $response = $this->classThatImplementTheTrait->responseUnAuthorized('Unauthorized', 'Unauthorized access');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $responseData = $response->getData();
        $this->assertEquals('Unauthorized', $responseData->errors[0]->detail);
    }

    #[Test]
    public function responseUnAuthenticated(): void
    {
        $response = $this->classThatImplementTheTrait->responseUnAuthenticated('Unauthenticated', 'Authentication required');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $responseData = $response->getData();
        $this->assertEquals('Unauthenticated', $responseData->errors[0]->detail);
    }

    #[Test]
    public function responseConflictError(): void
    {
        $response = $this->classThatImplementTheTrait->responseConflictError('Conflict', 'Resource conflict');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        $responseData = $response->getData();
        $this->assertEquals('Conflict', $responseData->errors[0]->detail);
    }

    #[Test]
    public function responseValidationError(): void
    {
        $validator = Validator::make([], [
            'field' => 'required',
        ]);

        try {
            $validator->validate();
        } catch (ValidationException $e) {
            $response = $this->classThatImplementTheTrait->ResponseValidationError($e);

            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

            $responseData = $response->getData();

            $this->assertIsArray($responseData->errors);
            $this->assertCount(1, $responseData->errors);

            $error = $responseData->errors[0];
            $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $error->status);
            $this->assertEquals('Validation Error', $error->title);
            $this->assertEquals('The field field is required.', $error->detail);
            $this->assertEquals(['pointer' => '/field'], (array) $error->source);
        }
    }

    #[Test]
    public function responseAccepted(): void
    {
        $response = $this->classThatImplementTheTrait->responseAccepted('Processing started', ['job_id' => '123']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $responseData = $response->getData();
        $this->assertEquals('Processing started', $responseData->message);
        $this->assertEquals(['job_id' => '123'], (array) $responseData->data);
    }

    #[Test]
    public function responseAcceptedWithDefaults(): void
    {
        $response = $this->classThatImplementTheTrait->responseAccepted();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $responseData = $response->getData();
        $this->assertEquals('Request accepted for processing', $responseData->message);
        $this->assertNull($responseData->data);
    }

    #[Test]
    public function responseNoContent(): void
    {
        $response = $this->classThatImplementTheTrait->responseNoContent();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('{}', $response->getContent());
    }
}
