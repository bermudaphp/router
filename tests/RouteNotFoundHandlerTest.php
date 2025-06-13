<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Middleware\RouteNotFoundHandler;
use Bermuda\Router\Exception\RouteNotFoundException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

#[Group('route-not-found-handler')]
#[TestDox('RouteNotFoundHandler tests')]
final class RouteNotFoundHandlerTest extends TestCase
{
    private ResponseFactoryInterface $responseFactory;
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;
    private ResponseInterface $response;
    private UriInterface $uri;
    private StreamInterface $body;

    protected function setUp(): void
    {
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->uri = $this->createMock(UriInterface::class);
        $this->body = $this->createMock(StreamInterface::class);
    }

    #[Test]
    #[TestDox('Can construct RouteNotFoundHandler with default parameters')]
    public function can_construct_with_default_parameters(): void
    {
        $handler = new RouteNotFoundHandler($this->responseFactory);
        $this->assertInstanceOf(RouteNotFoundHandler::class, $handler);
    }

    #[Test]
    #[TestDox('Can construct RouteNotFoundHandler with exception mode')]
    public function can_construct_with_exception_mode(): void
    {
        $handler = new RouteNotFoundHandler($this->responseFactory, true);
        $this->assertInstanceOf(RouteNotFoundHandler::class, $handler);
    }

    #[Test]
    #[TestDox('Can construct RouteNotFoundHandler with custom message')]
    public function can_construct_with_custom_message(): void
    {
        $handler = new RouteNotFoundHandler($this->responseFactory, false, 'Custom not found message');
        $this->assertInstanceOf(RouteNotFoundHandler::class, $handler);
    }

    #[Test]
    #[TestDox('Handle method throws exception when exception mode is enabled')]
    public function handle_method_throws_exception_when_exception_mode_is_enabled(): void
    {
        $this->setupRequestMock('/api/users/123', 'POST');

        $handler = new RouteNotFoundHandler($this->responseFactory, true);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route not found for POST /api/users/123');
        $this->expectExceptionCode(404);

        $handler->handle($this->request);
    }

    #[Test]
    #[TestDox('Handle method returns JSON response when exception mode is disabled')]
    public function handle_method_returns_json_response_when_exception_mode_is_disabled(): void
    {
        $this->setupRequestMock('/api/users/123', 'GET');
        $this->setupResponseMock();

        $handler = new RouteNotFoundHandler($this->responseFactory, false);
        $result = $handler->handle($this->request);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('Handle method uses custom message when provided')]
    public function handle_method_uses_custom_message_when_provided(): void
    {
        $customMessage = 'The API endpoint you requested does not exist.';

        $this->setupRequestMock('/api/nonexistent', 'GET');
        $this->setupResponseMock();

        // Capture the JSON that gets written to body
        $writtenJson = null;
        $this->body->expects($this->once())
            ->method('write')
            ->willReturnCallback(function($data) use (&$writtenJson) {
                $writtenJson = $data;
            });

        $handler = new RouteNotFoundHandler($this->responseFactory, false, $customMessage);
        $handler->handle($this->request);

        $this->assertNotNull($writtenJson);
        $decodedJson = json_decode($writtenJson, true);
        $this->assertEquals($customMessage, $decodedJson['message']);
    }

    #[Test]
    #[TestDox('Handle method includes correct error structure in JSON response')]
    public function handle_method_includes_correct_error_structure_in_json_response(): void
    {
        $this->setupRequestMock('/api/test', 'PUT');
        $this->setupResponseMock();

        // Capture the JSON that gets written
        $writtenJson = null;
        $this->body->expects($this->once())
            ->method('write')
            ->willReturnCallback(function($data) use (&$writtenJson) {
                $writtenJson = $data;
            });

        $handler = new RouteNotFoundHandler($this->responseFactory, false);
        $handler->handle($this->request);

        $this->assertNotNull($writtenJson);
        $decodedJson = json_decode($writtenJson, true);

        // Verify error structure
        $this->assertIsArray($decodedJson);
        $this->assertArrayHasKey('error', $decodedJson);
        $this->assertArrayHasKey('code', $decodedJson);
        $this->assertArrayHasKey('message', $decodedJson);
        $this->assertArrayHasKey('path', $decodedJson);
        $this->assertArrayHasKey('method', $decodedJson);
        $this->assertArrayHasKey('timestamp', $decodedJson);

        $this->assertEquals('Not Found', $decodedJson['error']);
        $this->assertEquals(404, $decodedJson['code']);
        $this->assertEquals('The requested endpoint was not found.', $decodedJson['message']);
        $this->assertEquals('/api/test', $decodedJson['path']);
        $this->assertEquals('PUT', $decodedJson['method']);

        // Verify timestamp is valid RFC3339 format
        $timestamp = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC3339, $decodedJson['timestamp']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $timestamp);
    }

    #[Test]
    #[TestDox('Process method behaves identically to handle method')]
    public function process_method_behaves_identically_to_handle_method(): void
    {
        $this->setupRequestMock('/test', 'GET');

        // Setup separate response mocks for handle and process calls
        $handleResponse = $this->createMock(ResponseInterface::class);
        $processResponse = $this->createMock(ResponseInterface::class);

        $this->responseFactory->expects($this->exactly(2))
            ->method('createResponse')
            ->with(404)
            ->willReturnOnConsecutiveCalls($handleResponse, $processResponse);

        // Setup body mocks
        $handleBody = $this->createMock(StreamInterface::class);
        $processBody = $this->createMock(StreamInterface::class);

        $handleBody->method('isWritable')->willReturn(true);
        $processBody->method('isWritable')->willReturn(true);

        $handleResponse->method('getBody')->willReturn($handleBody);
        $processResponse->method('getBody')->willReturn($processBody);

        $handleResponse->method('withHeader')->willReturnSelf();
        $processResponse->method('withHeader')->willReturnSelf();

        $handler = new RouteNotFoundHandler($this->responseFactory, false);

        $handleResult = $handler->handle($this->request);
        $processResult = $handler->process($this->request, $this->handler);

        // Both should return their respective response objects
        $this->assertSame($handleResponse, $handleResult);
        $this->assertSame($processResponse, $processResult);
    }

    #[Test]
    #[TestDox('withExceptionModeAttribute adds attribute to request')]
    public function with_exception_mode_attribute_adds_attribute_to_request(): void
    {
        $modifiedRequest = $this->createMock(ServerRequestInterface::class);

        $this->request->expects($this->once())
            ->method('withAttribute')
            ->with(RouteNotFoundHandler::REQUEST_ATTRIBUTE_EXCEPTION_MODE, true)
            ->willReturn($modifiedRequest);

        $handler = new RouteNotFoundHandler($this->responseFactory);
        $result = $handler->withExceptionModeAttribute($this->request, true);

        $this->assertSame($modifiedRequest, $result);
    }

    #[Test]
    #[TestDox('getExceptionMode returns constructor value when set')]
    public function get_exception_mode_returns_constructor_value_when_set(): void
    {
        $handlerTrue = new RouteNotFoundHandler($this->responseFactory, true);
        $handlerFalse = new RouteNotFoundHandler($this->responseFactory, false);

        $this->assertTrue($handlerTrue->getExceptionMode());
        $this->assertFalse($handlerFalse->getExceptionMode());
    }

    #[Test]
    #[TestDox('getExceptionMode returns request attribute when constructor value is null')]
    public function get_exception_mode_returns_request_attribute_when_constructor_value_is_null(): void
    {
        $handler = new RouteNotFoundHandler($this->responseFactory, null);

        // Mock request with attribute
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteNotFoundHandler::REQUEST_ATTRIBUTE_EXCEPTION_MODE, false)
            ->willReturn(true);

        $this->assertTrue($handler->getExceptionMode($this->request));
    }

    #[Test]
    #[TestDox('getExceptionMode defaults to false when no constructor value and no request')]
    public function get_exception_mode_defaults_to_false_when_no_constructor_value_and_no_request(): void
    {
        $handler = new RouteNotFoundHandler($this->responseFactory, null);
        $this->assertFalse($handler->getExceptionMode());
    }

    #[Test]
    #[TestDox('Constructor exception mode takes precedence over request attribute')]
    public function constructor_exception_mode_takes_precedence_over_request_attribute(): void
    {
        $handler = new RouteNotFoundHandler($this->responseFactory, true);

        // Mock request with different attribute value
        $this->request->method('getAttribute')->willReturn(false);

        // Constructor value should take precedence
        $this->assertTrue($handler->getExceptionMode($this->request));
    }

    #[Test]
    #[TestDox('Dynamic exception mode via request attribute works')]
    public function dynamic_exception_mode_via_request_attribute_works(): void
    {
        // Handler with null exception mode (dynamic)
        $handler = new RouteNotFoundHandler($this->responseFactory, null);

        // Request with exception mode = true
        $this->request->method('getAttribute')
            ->with(RouteNotFoundHandler::REQUEST_ATTRIBUTE_EXCEPTION_MODE, false)
            ->willReturn(true);

        $this->setupRequestMock('/dynamic/test', 'GET');

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route not found for GET /dynamic/test');

        $handler->handle($this->request);
    }

    #[Test]
    #[TestDox('Dynamic response mode via request attribute works')]
    public function dynamic_response_mode_via_request_attribute_works(): void
    {
        // Handler with null exception mode (dynamic)
        $handler = new RouteNotFoundHandler($this->responseFactory, null);

        // Request with exception mode = false
        $this->request->method('getAttribute')
            ->with(RouteNotFoundHandler::REQUEST_ATTRIBUTE_EXCEPTION_MODE, false)
            ->willReturn(false);

        $this->setupRequestMock('/dynamic/test', 'GET');
        $this->setupResponseMock();

        $result = $handler->handle($this->request);
        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('JSON response has correct Content-Type header')]
    public function json_response_has_correct_content_type_header(): void
    {
        $this->setupRequestMock('/test', 'GET');
        $this->setupResponseMock();

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json; charset=utf-8')
            ->willReturnSelf();

        $handler = new RouteNotFoundHandler($this->responseFactory, false);
        $handler->handle($this->request);
    }

    #[Test]
    #[TestDox('JSON is formatted with pretty print and unescaped slashes')]
    public function json_is_formatted_with_pretty_print_and_unescaped_slashes(): void
    {
        $this->setupRequestMock('/api/v1/users', 'GET');
        $this->setupResponseMock();

        $writtenJson = null;
        $this->body->expects($this->once())
            ->method('write')
            ->willReturnCallback(function($data) use (&$writtenJson) {
                $writtenJson = $data;
            });

        $handler = new RouteNotFoundHandler($this->responseFactory, false);
        $handler->handle($this->request);

        $this->assertNotNull($writtenJson);

        // Verify it's pretty printed (contains newlines and spaces)
        $this->assertStringContainsString("\n", $writtenJson);
        $this->assertStringContainsString("    ", $writtenJson); // 4-space indentation

        // Verify slashes are not escaped
        $this->assertStringContainsString('/api/v1/users', $writtenJson);
        $this->assertStringNotContainsString('\/api\/v1\/users', $writtenJson);
    }

    #[Test]
    #[TestDox('Handles body that is not writable gracefully')]
    public function handles_body_that_is_not_writable_gracefully(): void
    {
        $this->setupRequestMock('/test', 'GET');

        // Mock response factory
        $this->responseFactory->method('createResponse')->willReturn($this->response);

        // Mock non-writable body
        $nonWritableBody = $this->createMock(StreamInterface::class);
        $nonWritableBody->method('isWritable')->willReturn(false);
        $nonWritableBody->expects($this->never())->method('write'); // Should not be called

        $this->response->method('getBody')->willReturn($nonWritableBody);
        $this->response->method('withHeader')->willReturnSelf();

        $handler = new RouteNotFoundHandler($this->responseFactory, false);

        // Should not throw an exception even with non-writable body
        $result = $handler->handle($this->request);
        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('REQUEST_ATTRIBUTE_EXCEPTION_MODE constant has correct value')]
    public function request_attribute_exception_mode_constant_has_correct_value(): void
    {
        $expectedValue = 'Bermuda\Router\Middleware\RouteNotFoundHandler::exceptionMode';
        $this->assertEquals($expectedValue, RouteNotFoundHandler::REQUEST_ATTRIBUTE_EXCEPTION_MODE);
    }

    #[Test]
    #[TestDox('Different HTTP methods produce appropriate error messages')]
    public function different_http_methods_produce_appropriate_error_messages(): void
    {
        $httpMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

        foreach ($httpMethods as $method) {
            $handler = new RouteNotFoundHandler($this->responseFactory, true);

            $this->setupRequestMock('/test', $method);

            try {
                $handler->handle($this->request);
                $this->fail("Expected RouteNotFoundException for method {$method}");
            } catch (RouteNotFoundException $e) {
                $this->assertStringContainsString($method, $e->getMessage());
                $this->assertEquals($method, $e->requestMethod);
            }

            // Reset for next iteration
            $this->setUp();
        }
    }

    #[Test]
    #[TestDox('Complex URI paths are handled correctly')]
    public function complex_uri_paths_are_handled_correctly(): void
    {
        $complexPaths = [
            '/api/v1/users/123/posts/456/comments',
            '/admin/dashboard/statistics?filter=today',
            '/files/documents/project%20files/report.pdf',
            '/blog/2024/12/hello-world-post#introduction'
        ];

        foreach ($complexPaths as $path) {
            $handler = new RouteNotFoundHandler($this->responseFactory, true);

            $this->setupRequestMock($path, 'GET');

            try {
                $handler->handle($this->request);
                $this->fail("Expected RouteNotFoundException for path {$path}");
            } catch (RouteNotFoundException $e) {
                $this->assertEquals($path, $e->path);
                $this->assertStringContainsString($path, $e->getMessage());
            }

            // Reset for next iteration
            $this->setUp();
        }
    }

    /**
     * Helper method to setup request mock
     */
    private function setupRequestMock(string $path, string $method = 'GET'): void
    {
        $this->uri->method('getPath')->willReturn($path);
        $this->request->method('getUri')->willReturn($this->uri);
        $this->request->method('getMethod')->willReturn($method);
    }

    /**
     * Helper method to setup response mock for JSON responses
     */
    private function setupResponseMock(): void
    {
        $this->responseFactory->expects($this->once())
            ->method('createResponse')
            ->with(404)
            ->willReturn($this->response);

        $this->body->method('isWritable')->willReturn(true);
        $this->response->method('getBody')->willReturn($this->body);
        $this->response->method('withHeader')->willReturnSelf();
    }

    #[Test]
    #[TestDox('Performance test for error response generation')]
    public function performance_test_for_error_response_generation(): void
    {
        $this->setupRequestMock('/performance/test', 'GET');

        // Create separate response instances for performance test
        $responses = [];
        $bodies = [];

        for ($i = 0; $i < 1000; $i++) {
            $responses[$i] = $this->createMock(ResponseInterface::class);
            $bodies[$i] = $this->createMock(StreamInterface::class);

            $bodies[$i]->method('isWritable')->willReturn(true);
            $responses[$i]->method('getBody')->willReturn($bodies[$i]);
            $responses[$i]->method('withHeader')->willReturnSelf();
        }

        $this->responseFactory->expects($this->exactly(1000))
            ->method('createResponse')
            ->with(404)
            ->willReturnOnConsecutiveCalls(...$responses);

        $handler = new RouteNotFoundHandler($this->responseFactory, false);

        $iterations = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $handler->handle($this->request);
        }

        $totalTime = microtime(true) - $startTime;

        // Should generate 1000 error responses in reasonable time
        $this->assertLessThan(1.0, $totalTime,
            "Error response generation is too slow: {$totalTime}s for {$iterations} iterations");
    }
}