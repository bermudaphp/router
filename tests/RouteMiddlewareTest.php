<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Middleware\RouteMiddleware;
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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;

#[Group('route-middleware')]
#[TestDox('RouteMiddleware tests')]
final class RouteMiddlewareTest extends TestCase
{
    private MiddlewareFactoryInterface $middlewareFactory;
    private PipelineFactoryInterface $pipelineFactory;
    private RouteRecord $route;
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;
    private ResponseInterface $response;
    private MiddlewareInterface $createdMiddleware;
    private PipelineInterface $createdPipeline;

    protected function setUp(): void
    {
        $this->middlewareFactory = $this->createMock(MiddlewareFactoryInterface::class);
        $this->pipelineFactory = $this->createMock(PipelineFactoryInterface::class);
        $this->route = RouteRecord::get('test.route', '/test/[id]', 'TestController')
            ->withMiddlewares(['AuthMiddleware', 'ValidationMiddleware']);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->createdMiddleware = $this->createMock(MiddlewareInterface::class);
        $this->createdPipeline = $this->createMock(PipelineInterface::class);
    }

    #[Test]
    #[TestDox('Can construct RouteMiddleware with dependencies')]
    public function can_construct_with_dependencies(): void
    {
        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $this->route);

        $this->assertInstanceOf(RouteMiddleware::class, $routeMiddleware);
        $this->assertSame($this->route, $routeMiddleware->route);
    }

    #[Test]
    #[TestDox('Process method creates middleware from route pipeline')]
    public function process_method_creates_middleware_from_route_pipeline(): void
    {
        // Route has multiple middlewares, so pipeline factory should be called
        $this->pipelineFactory->expects($this->once())
            ->method('createMiddlewarePipeline')
            ->with(['AuthMiddleware', 'ValidationMiddleware', 'TestController'])
            ->willReturn($this->createdPipeline);

        // Mock middleware factory to return our mock middleware from pipeline object
        $this->middlewareFactory->expects($this->once())
            ->method('makeMiddleware')
            ->with($this->createdPipeline)
            ->willReturn($this->createdMiddleware);

        // Mock the created middleware to process the request
        $this->createdMiddleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->handler)
            ->willReturn($this->response);

        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $this->route, $this->pipelineFactory);
        $result = $routeMiddleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('Handle method works as standalone request handler')]
    public function handle_method_works_as_standalone_request_handler(): void
    {
        // Route has multiple middlewares, so pipeline should be created
        $this->pipelineFactory->expects($this->once())
            ->method('createMiddlewarePipeline')
            ->with(['AuthMiddleware', 'ValidationMiddleware', 'TestController'])
            ->willReturn($this->createdPipeline);

        // Mock middleware factory
        $this->middlewareFactory->expects($this->once())
            ->method('makeMiddleware')
            ->with($this->createdPipeline)
            ->willReturn($this->createdMiddleware);

        // Mock the created middleware - it should receive the fallback handler
        $this->createdMiddleware->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($this->request),
                $this->callback(function($handler) {
                    // The fallback handler should throw RuntimeException when called
                    try {
                        $handler->handle($this->createMock(ServerRequestInterface::class));
                        return false; // Should have thrown exception
                    } catch (\RuntimeException $e) {
                        return $e->getMessage() === 'Empty request handler';
                    }
                })
            )
            ->willReturn($this->response);

        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $this->route, $this->pipelineFactory);
        $result = $routeMiddleware->handle($this->request);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('withRouteAttribute adds middleware to request attributes')]
    public function with_route_attribute_adds_middleware_to_request_attributes(): void
    {
        $modifiedRequest = $this->createMock(ServerRequestInterface::class);

        $this->request->expects($this->once())
            ->method('withAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE, $this->isInstanceOf(RouteMiddleware::class))
            ->willReturn($modifiedRequest);

        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $this->route);
        $result = $routeMiddleware->withRouteAttribute($this->request);

        $this->assertSame($modifiedRequest, $result);
    }

    #[Test]
    #[TestDox('createAndAttachToRequest creates and attaches middleware in one step')]
    public function create_and_attach_to_request_creates_and_attaches_middleware_in_one_step(): void
    {
        $modifiedRequest = $this->createMock(ServerRequestInterface::class);

        $this->request->expects($this->once())
            ->method('withAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE, $this->isInstanceOf(RouteMiddleware::class))
            ->willReturn($modifiedRequest);

        $result = RouteMiddleware::createAndAttachToRequest(
            $this->request,
            $this->middlewareFactory,
            $this->route
        );

        $this->assertSame($modifiedRequest, $result);
    }

    #[Test]
    #[TestDox('fromRequest extracts RouteMiddleware from request attributes')]
    public function from_request_extracts_route_middleware_from_request_attributes(): void
    {
        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $this->route);

        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn($routeMiddleware);

        $result = RouteMiddleware::fromRequest($this->request);

        $this->assertSame($routeMiddleware, $result);
    }

    #[Test]
    #[TestDox('fromRequest returns null when RouteMiddleware not found')]
    public function from_request_returns_null_when_route_middleware_not_found(): void
    {
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn(null);

        $result = RouteMiddleware::fromRequest($this->request);

        $this->assertNull($result);
    }

    #[Test]
    #[TestDox('fromRequest returns null when attribute is not RouteMiddleware instance')]
    public function from_request_returns_null_when_attribute_is_not_route_middleware_instance(): void
    {
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn('not-a-route-middleware');

        $result = RouteMiddleware::fromRequest($this->request);

        $this->assertNull($result);
    }

    #[Test]
    #[TestDox('fromRequestOrFail extracts RouteMiddleware successfully')]
    public function from_request_or_fail_extracts_route_middleware_successfully(): void
    {
        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $this->route);

        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn($routeMiddleware);

        $result = RouteMiddleware::fromRequestOrFail($this->request);

        $this->assertSame($routeMiddleware, $result);
    }

    #[Test]
    #[TestDox('fromRequestOrFail throws exception when RouteMiddleware not found')]
    public function from_request_or_fail_throws_exception_when_route_middleware_not_found(): void
    {
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('RouteMiddleware not found in request attributes');

        RouteMiddleware::fromRequestOrFail($this->request);
    }

    #[Test]
    #[TestDox('fromRequestOrFail throws exception when attribute is wrong type')]
    public function from_request_or_fail_throws_exception_when_attribute_is_wrong_type(): void
    {
        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn('wrong-type');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('RouteMiddleware not found in request attributes');

        RouteMiddleware::fromRequestOrFail($this->request);
    }

    #[Test]
    #[TestDox('Route pipeline includes middleware and handler')]
    public function route_pipeline_includes_middleware_and_handler(): void
    {
        $route = RouteRecord::get('test', '/test', 'TestHandler')
            ->withMiddlewares(['Middleware1', 'Middleware2']);

        // Multiple middlewares, so pipeline should be created
        $this->pipelineFactory->expects($this->once())
            ->method('createMiddlewarePipeline')
            ->with(['Middleware1', 'Middleware2', 'TestHandler'])
            ->willReturn($this->createdPipeline);

        $this->middlewareFactory->expects($this->once())
            ->method('makeMiddleware')
            ->with($this->createdPipeline)
            ->willReturn($this->createdMiddleware);

        $this->createdMiddleware->expects($this->once())
            ->method('process')
            ->willReturn($this->response);

        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $route, $this->pipelineFactory);
        $result = $routeMiddleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('Works with route without middleware')]
    public function works_with_route_without_middleware(): void
    {
        $route = RouteRecord::get('simple', '/simple', 'SimpleHandler');

        // Only one element in pipeline, so should pass the handler string directly
        $this->middlewareFactory->expects($this->once())
            ->method('makeMiddleware')
            ->with('SimpleHandler')  // Single element passed as string
            ->willReturn($this->createdMiddleware);

        $this->createdMiddleware->expects($this->once())
            ->method('process')
            ->willReturn($this->response);

        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $route, $this->pipelineFactory);
        $result = $routeMiddleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('REQUEST_ATTRIBUTE constant has correct value')]
    public function request_attribute_constant_has_correct_value(): void
    {
        $this->assertEquals(RouteMiddleware::class, RouteMiddleware::REQUEST_ATTRIBUTE);
    }

    #[Test]
    #[TestDox('Route property is accessible')]
    public function route_property_is_accessible(): void
    {
        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $this->route);

        $this->assertSame($this->route, $routeMiddleware->route);
    }

    #[Test]
    #[TestDox('Integration test with complete flow')]
    public function integration_test_with_complete_flow(): void
    {
        // Create a route with parameters
        $route = RouteRecord::get('users.show', '/users/[id]', 'UserController')
            ->withParameters(['id' => 123])
            ->withMiddlewares(['AuthMiddleware']);

        // Create RouteMiddleware
        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $route);

        // Add to request
        $modifiedRequest = $this->createMock(ServerRequestInterface::class);
        $this->request->expects($this->once())
            ->method('withAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE, $routeMiddleware)
            ->willReturn($modifiedRequest);

        $requestWithRoute = $routeMiddleware->withRouteAttribute($this->request);

        // Verify we can extract it back
        $modifiedRequest->expects($this->once())
            ->method('getAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE)
            ->willReturn($routeMiddleware);

        $extracted = RouteMiddleware::fromRequest($requestWithRoute);

        $this->assertSame($routeMiddleware, $extracted);
        $this->assertSame($route, $extracted->route);
        $this->assertEquals('users.show', $extracted->route->name);
        $this->assertEquals(123, $extracted->route->parameters->get('id'));
    }

    #[Test]
    #[TestDox('Handle method fallback handler throws expected exception')]
    public function handle_method_fallback_handler_throws_expected_exception(): void
    {
        // Route with multiple middlewares
        $this->pipelineFactory->expects($this->once())
            ->method('createMiddlewarePipeline')
            ->willReturn($this->createdPipeline);

        // Setup so the created middleware will call the fallback handler
        $this->middlewareFactory->method('makeMiddleware')->willReturn($this->createdMiddleware);

        $this->createdMiddleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function($request, $handler) {
                // Simulate the middleware calling the next handler
                return $handler->handle($request);
            });

        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $this->route, $this->pipelineFactory);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Empty request handler');

        $routeMiddleware->handle($this->request);
    }

    #[Test]
    #[TestDox('Multiple RouteMiddleware instances can coexist')]
    public function multiple_route_middleware_instances_can_coexist(): void
    {
        $route1 = RouteRecord::get('route1', '/route1', 'Handler1');
        $route2 = RouteRecord::get('route2', '/route2', 'Handler2');

        $middleware1 = new RouteMiddleware($this->middlewareFactory, $route1);
        $middleware2 = new RouteMiddleware($this->middlewareFactory, $route2);

        $this->assertNotSame($middleware1, $middleware2);
        $this->assertSame($route1, $middleware1->route);
        $this->assertSame($route2, $middleware2->route);
        $this->assertEquals('route1', $middleware1->route->name);
        $this->assertEquals('route2', $middleware2->route->name);
    }

    #[Test]
    #[TestDox('Middleware factory is called with correct pipeline data')]
    public function middleware_factory_is_called_with_correct_pipeline_data(): void
    {
        $route = RouteRecord::get('complex', '/complex', 'ComplexHandler')
            ->withMiddlewares(['Auth', 'Validation', 'Cache']);

        // Multiple middlewares, so pipeline should be created
        $this->pipelineFactory->expects($this->once())
            ->method('createMiddlewarePipeline')
            ->with(['Auth', 'Validation', 'Cache', 'ComplexHandler'])
            ->willReturn($this->createdPipeline);

        $this->middlewareFactory->expects($this->once())
            ->method('makeMiddleware')
            ->with($this->createdPipeline)
            ->willReturn($this->createdMiddleware);

        $this->createdMiddleware->method('process')->willReturn($this->response);

        $routeMiddleware = new RouteMiddleware($this->middlewareFactory, $route, $this->pipelineFactory);
        $routeMiddleware->process($this->request, $this->handler);
    }
}