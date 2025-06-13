<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Middleware\DispatchRouteMiddleware;
use Bermuda\Router\Middleware\RouteMiddleware;
use Bermuda\Router\Exception\RouteNotFoundException;
use Bermuda\Router\RouteRecord;
use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;
use Bermuda\Pipeline\PipelineFactoryInterface;
use Bermuda\Pipeline\PipelineInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

#[Group('dispatch-middleware')]
#[TestDox('DispatchRouteMiddleware tests')]
final class DispatchRouteMiddlewareTest extends TestCase
{
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;
    private ResponseInterface $response;

    protected function setUp(): void
    {
        // Mock objects
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    #[Test]
    #[TestDox('Can construct DispatchRouteMiddleware without handler')]
    public function can_construct_without_handler(): void
    {
        $middleware = new DispatchRouteMiddleware();
        $this->assertInstanceOf(DispatchRouteMiddleware::class, $middleware);
    }

    #[Test]
    #[TestDox('Can construct DispatchRouteMiddleware with handler')]
    public function can_construct_with_handler(): void
    {
        $fallbackHandler = $this->createMock(RequestHandlerInterface::class);
        $middleware = new DispatchRouteMiddleware($fallbackHandler);
        $this->assertInstanceOf(DispatchRouteMiddleware::class, $middleware);
    }

    #[Test]
    #[TestDox('Processes request when RouteMiddleware is found in request attributes')]
    public function processes_request_when_route_middleware_is_found_in_request_attributes(): void
    {
        // Create a real RouteMiddleware instance
        $middlewareFactory = $this->createMock(MiddlewareFactoryInterface::class);
        $route = RouteRecord::get('test', '/test', 'TestController');
        $routeMiddleware = new RouteMiddleware($middlewareFactory, $route);

        // Create a test middleware that the factory will return
        $testMiddleware = $this->createMock(MiddlewareInterface::class);
        $testMiddleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->handler)
            ->willReturn($this->response);

        // Configure the middleware factory
        $middlewareFactory->expects($this->once())
            ->method('makeMiddleware')
            ->with('TestController')
            ->willReturn($testMiddleware);

        // Mock request to return the RouteMiddleware from attributes
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn($routeMiddleware);

        $middleware = new DispatchRouteMiddleware();
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('Uses fallback handler when RouteMiddleware not found and fallback provided')]
    public function uses_fallback_handler_when_route_middleware_not_found_and_fallback_provided(): void
    {
        // Mock request to return null for RouteMiddleware (not found)
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn(null);

        // Create fallback handler
        $fallbackHandler = $this->createMock(RequestHandlerInterface::class);
        $fallbackHandler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $middleware = new DispatchRouteMiddleware($fallbackHandler);
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('Throws RouteNotFoundException when no RouteMiddleware and no fallback handler')]
    public function throws_route_not_found_exception_when_no_route_middleware_and_no_fallback_handler(): void
    {
        // Mock request to return null for RouteMiddleware (not found)
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn(null);

        // Mock request methods for RouteNotFoundException
        $this->request->expects($this->once())
            ->method('getUri')
            ->willReturn($this->createMockUri('/test/path'));

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $middleware = new DispatchRouteMiddleware(); // No fallback handler

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route not found for GET /test/path');

        $middleware->process($this->request, $this->handler);
    }

    #[Test]
    #[TestDox('RouteNotFoundException is thrown with correct parameters')]
    public function route_not_found_exception_is_thrown_with_correct_parameters(): void
    {
        // Mock request to return null for RouteMiddleware (not found)
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn(null);

        // Mock request methods for RouteNotFoundException
        $this->request->expects($this->once())
            ->method('getUri')
            ->willReturn($this->createMockUri('/api/users/123'));

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $middleware = new DispatchRouteMiddleware(); // No fallback handler

        try {
            $middleware->process($this->request, $this->handler);
            $this->fail('Expected RouteNotFoundException to be thrown');
        } catch (RouteNotFoundException $e) {
            // Verify that the exception was created correctly
            $this->assertEquals('/api/users/123', $e->path);
            $this->assertEquals('POST', $e->requestMethod);
            $this->assertEquals('Route not found for POST /api/users/123', $e->getMessage());
            $this->assertEquals(404, $e->getCode());
        }
    }

    #[Test]
    #[TestDox('Handles different HTTP methods correctly')]
    public function handles_different_http_methods_correctly(): void
    {
        $httpMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

        foreach ($httpMethods as $method) {
            // Mock request to return null for RouteMiddleware (not found)
            $request = $this->createMock(ServerRequestInterface::class);
            $request->method('getAttribute')->willReturn(null);
            $request->method('getUri')->willReturn($this->createMockUri('/test'));
            $request->method('getMethod')->willReturn($method);

            $middleware = new DispatchRouteMiddleware();

            try {
                $middleware->process($request, $this->handler);
                $this->fail("Expected RouteNotFoundException for method {$method}");
            } catch (RouteNotFoundException $e) {
                $this->assertEquals($method, $e->requestMethod, "Method should be {$method}");
                $this->assertStringContainsString($method, $e->getMessage(), "Message should contain {$method}");
            }
        }
    }

    #[Test]
    #[TestDox('Handles complex URIs correctly')]
    public function handles_complex_uris_correctly(): void
    {
        $testUris = [
            '/api/v1/users/123/posts/456',
            '/search?q=test&category=tech',
            '/admin/dashboard#statistics',
            '/files/folder%20with%20spaces/file.txt'
        ];

        foreach ($testUris as $uri) {
            $request = $this->createMock(ServerRequestInterface::class);
            $request->method('getAttribute')->willReturn(null);
            $request->method('getUri')->willReturn($this->createMockUri($uri));
            $request->method('getMethod')->willReturn('GET');

            $middleware = new DispatchRouteMiddleware();

            try {
                $middleware->process($request, $this->handler);
                $this->fail("Expected RouteNotFoundException for URI {$uri}");
            } catch (RouteNotFoundException $e) {
                $this->assertEquals($uri, $e->path, "Path should be {$uri}");
                $this->assertStringContainsString($uri, $e->getMessage(), "Message should contain {$uri}");
            }
        }
    }

    #[Test]
    #[TestDox('Fallback handler receives correct parameters')]
    public function fallback_handler_receives_correct_parameters(): void
    {
        // Mock request to return null for RouteMiddleware
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->willReturn(null);

        // Create fallback handler that verifies parameters
        $fallbackHandler = $this->createMock(RequestHandlerInterface::class);

        // The fallback handler should be called with handle()
        $fallbackHandler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($this->request))
            ->willReturn($this->response);

        $middleware = new DispatchRouteMiddleware($fallbackHandler);
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('RouteMiddleware process method receives correct parameters')]
    public function route_middleware_process_method_receives_correct_parameters(): void
    {
        // Create a real RouteMiddleware instance
        $middlewareFactory = $this->createMock(MiddlewareFactoryInterface::class);
        $route = RouteRecord::get('test', '/test', 'TestController');
        $routeMiddleware = new RouteMiddleware($middlewareFactory, $route);

        // Mock the middleware that will be created by the factory
        $testMiddleware = $this->createMock(MiddlewareInterface::class);
        $testMiddleware->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($this->request),
                $this->identicalTo($this->handler)
            )
            ->willReturn($this->response);

        $middlewareFactory->expects($this->once())
            ->method('makeMiddleware')
            ->with('TestController')
            ->willReturn($testMiddleware);

        // Mock request to return our RouteMiddleware
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn($routeMiddleware);

        $middleware = new DispatchRouteMiddleware();
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('Works correctly with routes that have middleware pipeline')]
    public function works_correctly_with_routes_that_have_middleware_pipeline(): void
    {
        // Create route with middleware chain
        $middlewareFactory = $this->createMock(MiddlewareFactoryInterface::class);
        $pipelineFactory = $this->createMock(PipelineFactoryInterface::class);

        $route = RouteRecord::get('test', '/test', 'TestController')
            ->withMiddlewares(['AuthMiddleware', 'ValidationMiddleware']);

        $routeMiddleware = new RouteMiddleware($middlewareFactory, $route, $pipelineFactory);

        // Mock the pipeline that will be created from multiple middleware
        $mockPipeline = $this->createMock(PipelineInterface::class);
        $pipelineFactory->expects($this->once())
            ->method('createMiddlewarePipeline')
            ->with(['AuthMiddleware', 'ValidationMiddleware', 'TestController'])
            ->willReturn($mockPipeline);

        // Mock the final middleware that will be created from the pipeline
        $finalMiddleware = $this->createMock(MiddlewareInterface::class);
        $finalMiddleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->handler)
            ->willReturn($this->response);

        $middlewareFactory->expects($this->once())
            ->method('makeMiddleware')
            ->with($mockPipeline)
            ->willReturn($finalMiddleware);

        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn($routeMiddleware);

        $middleware = new DispatchRouteMiddleware();
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('Correctly identifies when fallback handler is present vs absent')]
    public function correctly_identifies_when_fallback_handler_is_present_vs_absent(): void
    {
        // Test with fallback handler present
        $fallbackHandler = $this->createMock(RequestHandlerInterface::class);
        $fallbackResponse = $this->createMock(ResponseInterface::class);

        $fallbackHandler->expects($this->once())
            ->method('handle')
            ->willReturn($fallbackResponse);

        $request1 = $this->createMock(ServerRequestInterface::class);
        $request1->method('getAttribute')->willReturn(null); // No RouteMiddleware

        $middleware1 = new DispatchRouteMiddleware($fallbackHandler);

        try {
            $result = $middleware1->process($request1, $this->handler);
            $fallbackUsed = true;
            $this->assertSame($fallbackResponse, $result);
        } catch (RouteNotFoundException $e) {
            $fallbackUsed = false;
        }

        // Test with no fallback handler
        $request2 = $this->createMock(ServerRequestInterface::class);
        $request2->method('getAttribute')->willReturn(null); // No RouteMiddleware
        $request2->method('getUri')->willReturn($this->createMockUri('/test'));
        $request2->method('getMethod')->willReturn('GET');

        $middleware2 = new DispatchRouteMiddleware(); // No fallback handler

        try {
            $middleware2->process($request2, $this->handler);
            $exceptionThrown = false;
        } catch (RouteNotFoundException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($fallbackUsed, 'Should use fallback handler when present');
        $this->assertTrue($exceptionThrown, 'Should throw exception when no fallback handler');
    }

    #[Test]
    #[TestDox('Integration test with realistic routing scenario')]
    public function integration_test_with_realistic_routing_scenario(): void
    {
        // Create a realistic route
        $middlewareFactory = $this->createMock(MiddlewareFactoryInterface::class);
        $route = RouteRecord::get('api.users.show', '/api/users/[id]', 'Api\\UserController');
        $routeMiddleware = new RouteMiddleware($middlewareFactory, $route);

        // Create request with RouteMiddleware attached
        $requestWithRoute = $this->createMock(ServerRequestInterface::class);
        $requestWithRoute->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn($routeMiddleware);

        // Mock the controller middleware
        $controllerMiddleware = $this->createMock(MiddlewareInterface::class);
        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('getStatusCode')->willReturn(200);

        $controllerMiddleware->expects($this->once())
            ->method('process')
            ->willReturn($apiResponse);

        $middlewareFactory->expects($this->once())
            ->method('makeMiddleware')
            ->with('Api\\UserController')
            ->willReturn($controllerMiddleware);

        $middleware = new DispatchRouteMiddleware();
        $result = $middleware->process($requestWithRoute, $this->handler);

        $this->assertSame($apiResponse, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    #[TestDox('Demonstrates the bug fix for RouteNotFoundException parameters')]
    public function demonstrates_the_bug_fix_for_route_not_found_exception_parameters(): void
    {
        // Mock the scenario
        $this->request->method('getAttribute')->willReturn(null);
        $this->request->method('getUri')->willReturn($this->createMockUri('/test'));
        $this->request->method('getMethod')->willReturn('GET');

        $middleware = new DispatchRouteMiddleware();

        $this->expectException(RouteNotFoundException::class);
        $middleware->process($this->request, $this->handler);
    }

    /**
     * Helper method to create a mock URI
     */
    private function createMockUri(string $path): object
    {
        $uri = $this->createMock(\Psr\Http\Message\UriInterface::class);
        $uri->method('getPath')->willReturn($path);
        $uri->method('__toString')->willReturn($path);
        return $uri;
    }
}