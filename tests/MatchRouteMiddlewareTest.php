<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\Middleware\MatchRouteMiddleware;
use Bermuda\Router\Middleware\RouteMiddleware;
use Bermuda\Router\Router;
use Bermuda\Router\Routes;
use Bermuda\Router\RouteRecord;
use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Container\ContainerInterface;

#[Group('match-route-middleware')]
#[TestDox('MatchRouteMiddleware tests')]
final class MatchRouteMiddlewareTest extends TestCase
{
    private MiddlewareFactoryInterface $middlewareFactory;
    private Routes $routes;
    private Router $router;
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;
    private ResponseInterface $response;
    private UriInterface $uri;

    protected function setUp(): void
    {
        $this->middlewareFactory = $this->createMock(MiddlewareFactoryInterface::class);
        $this->routes = new Routes();
        $this->router = Router::fromDnf($this->routes);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->uri = $this->createMock(UriInterface::class);
    }

    #[Test]
    #[TestDox('Can construct MatchRouteMiddleware with dependencies')]
    public function can_construct_with_dependencies(): void
    {
        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);

        $this->assertInstanceOf(MatchRouteMiddleware::class, $middleware);
    }

    #[Test]
    #[TestDox('Process continues to next handler when no route matches')]
    public function process_continues_to_next_handler_when_no_route_matches(): void
    {
        // Mock request URI
        $this->uri->method('__toString')->willReturn('/non-existent-route');
        $this->request->method('getUri')->willReturn($this->uri);
        $this->request->method('getMethod')->willReturn('GET');

        // Mock handler to return response
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
        $this->assertNull($middleware->matchedRoute);
    }

    #[Test]
    #[TestDox('Process adds route parameters as request attributes when route matches')]
    public function process_adds_route_parameters_as_request_attributes_when_route_matches(): void
    {
        // Create a route with parameter that will be extracted from URL
        $route = RouteRecord::get('users.show', '/users/[id]', 'UserController');

        $this->routes->addRoute($route);

        // Mock request URI that matches the pattern
        $this->uri->method('__toString')->willReturn('/users/123');
        $this->request->method('getUri')->willReturn($this->uri);
        $this->request->method('getMethod')->willReturn('GET');

        // Mock withAttribute to return new request objects for chain
        $this->request->method('withAttribute')
            ->willReturn($this->request); // Return self for simplicity in testing

        // Mock handler to verify it receives a request
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(ServerRequestInterface::class))
            ->willReturn($this->response);

        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
        $this->assertNotNull($middleware->matchedRoute);
        $this->assertEquals('users.show', $middleware->matchedRoute->name);
        // Only check for parameter that is actually extracted from URL pattern
        $this->assertEquals('123', $middleware->matchedRoute->parameters->get('id'));
    }

    #[Test]
    #[TestDox('Process creates and attaches RouteMiddleware when route matches')]
    public function process_creates_and_attaches_route_middleware_when_route_matches(): void
    {
        // Create a simple route without parameters to simplify test
        $route = RouteRecord::get('home', '/', 'HomeController');
        $this->routes->addRoute($route);

        // Mock request
        $this->uri->method('__toString')->willReturn('/');
        $this->request->method('getUri')->willReturn($this->uri);
        $this->request->method('getMethod')->willReturn('GET');

        // Mock request with RouteMiddleware attribute
        $requestWithRouteMiddleware = $this->createMock(ServerRequestInterface::class);
        $this->request->expects($this->once())
            ->method('withAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE, $this->isInstanceOf(RouteMiddleware::class))
            ->willReturn($requestWithRouteMiddleware);

        // Mock handler
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($requestWithRouteMiddleware)
            ->willReturn($this->response);

        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
        $this->assertNotNull($middleware->matchedRoute);
    }

    #[Test]
    #[TestDox('matchedRoute property is updated correctly')]
    public function matched_route_property_is_updated_correctly(): void
    {
        $route1 = RouteRecord::get('route1', '/route1', 'Handler1');
        $route2 = RouteRecord::get('route2', '/route2', 'Handler2');

        $this->routes->addRoute($route1);
        $this->routes->addRoute($route2);

        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);

        // Initially null
        $this->assertNull($middleware->matchedRoute);

        // Mock first request - need to setup proper mocking for each call
        $this->setupRequestMockForMatching('/route1');
        $middleware->process($this->request, $this->handler);
        $this->assertNotNull($middleware->matchedRoute);
        $this->assertEquals('route1', $middleware->matchedRoute->name);

        // Reset and mock second request with different route
        $this->setUp();
        $this->routes->addRoute($route1);
        $this->routes->addRoute($route2);
        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);

        $this->setupRequestMockForMatching('/route2');
        $middleware->process($this->request, $this->handler);
        $this->assertEquals('route2', $middleware->matchedRoute->name);

        // Reset and mock third request with no match
        $this->setUp();
        $this->routes->addRoute($route1);
        $this->routes->addRoute($route2);
        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);

        $this->setupRequestMock('/non-existent');
        $this->handler->method('handle')->willReturn($this->response);
        $middleware->process($this->request, $this->handler);
        $this->assertNull($middleware->matchedRoute);
    }

    #[Test]
    #[TestDox('createFromContainer creates instance with resolved dependencies')]
    public function create_from_container_creates_instance_with_resolved_dependencies(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function($service) {
                return match($service) {
                    MiddlewareFactoryInterface::class => $this->middlewareFactory,
                    Router::class => $this->router,
                    default => throw new \Exception("Unexpected service: $service")
                };
            });

        $middleware = MatchRouteMiddleware::createFromContainer($container);

        $this->assertInstanceOf(MatchRouteMiddleware::class, $middleware);
    }

    #[Test]
    #[TestDox('Handles different HTTP methods correctly')]
    public function handles_different_http_methods_correctly(): void
    {
        $httpMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

        foreach ($httpMethods as $method) {
            // Reset setup for each iteration
            $this->setUp();

            $route = RouteRecord::any('test', '/test', 'TestController', [$method]);
            $this->routes->addRoute($route);
            $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);

            $this->setupRequestMockForMatching('/test', $method);
            $result = $middleware->process($this->request, $this->handler);

            $this->assertSame($this->response, $result);
            $this->assertNotNull($middleware->matchedRoute);
        }
    }

    #[Test]
    #[TestDox('Handles complex URIs correctly')]
    public function handles_complex_uris_correctly(): void
    {
        $testCases = [
            ['/api/v1/users/123', 'GET', 'api.users.show'],
            ['/admin/posts/456/edit', 'POST', 'admin.posts.edit'],
            ['/blog/2024/12/hello-world', 'GET', 'blog.post'],
            ['/search', 'GET', 'search'],
        ];

        foreach ($testCases as [$uri, $method, $routeName]) {
            // Reset setup for each iteration
            $this->setUp();

            $route = RouteRecord::any($routeName, $uri, 'TestController');
            $this->routes->addRoute($route);
            $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);

            $this->setupRequestMockForMatching($uri, $method);
            $result = $middleware->process($this->request, $this->handler);

            $this->assertSame($this->response, $result);
            $this->assertNotNull($middleware->matchedRoute);
        }
    }

    #[Test]
    #[TestDox('Works correctly with routes that have no parameters')]
    public function works_correctly_with_routes_that_have_no_parameters(): void
    {
        $route = RouteRecord::get('static', '/static-page', 'StaticController');
        $this->routes->addRoute($route);

        $this->setupRequestMock('/static-page');

        // Request should only have RouteMiddleware attribute added (no parameters)
        $requestWithRouteMiddleware = $this->createMock(ServerRequestInterface::class);
        $this->request->expects($this->once())
            ->method('withAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE, $this->isInstanceOf(RouteMiddleware::class))
            ->willReturn($requestWithRouteMiddleware);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($requestWithRouteMiddleware)
            ->willReturn($this->response);

        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
        $this->assertNotNull($middleware->matchedRoute);
    }

    #[Test]
    #[TestDox('Preserves request immutability')]
    public function preserves_request_immutability(): void
    {
        // Create route with pattern that has multiple parameters
        $route = RouteRecord::get('test', '/test/[param1]/[param2]', 'TestController');

        $this->routes->addRoute($route);
        $this->setupRequestMock('/test/value1/value2');

        // Create different mock requests for each withAttribute call
        $originalRequest = $this->request;
        $requestAfterParam1 = $this->createMock(ServerRequestInterface::class);
        $requestAfterParam2 = $this->createMock(ServerRequestInterface::class);
        $finalRequest = $this->createMock(ServerRequestInterface::class);

        // Set up the chain of withAttribute calls for URL-extracted parameters
        $originalRequest->expects($this->once())
            ->method('withAttribute')
            ->with('param1', 'value1')
            ->willReturn($requestAfterParam1);

        $requestAfterParam1->expects($this->once())
            ->method('withAttribute')
            ->with('param2', 'value2')
            ->willReturn($requestAfterParam2);

        $requestAfterParam2->expects($this->once())
            ->method('withAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE, $this->isInstanceOf(RouteMiddleware::class))
            ->willReturn($finalRequest);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($finalRequest)
            ->willReturn($this->response);

        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);
        $result = $middleware->process($originalRequest, $this->handler);

        $this->assertSame($this->response, $result);
    }

    #[Test]
    #[TestDox('Integration test with realistic scenario')]
    public function integration_test_with_realistic_scenario(): void
    {
        // Create a realistic route with parameter in URL pattern
        $route = RouteRecord::get('api.users.show', '/api/users/[id]', 'Api\\UserController')
            ->withMiddlewares(['AuthMiddleware', 'RateLimitMiddleware']);

        $this->routes->addRoute($route);
        $this->setupRequestMock('/api/users/123', 'GET');

        // Set up request attribute chain
        $requestWithId = $this->createMock(ServerRequestInterface::class);
        $requestWithRouteMiddleware = $this->createMock(ServerRequestInterface::class);

        $this->request->expects($this->once())
            ->method('withAttribute')
            ->with('id', '123') // Parameters extracted from URL are strings
            ->willReturn($requestWithId);

        $requestWithId->expects($this->once())
            ->method('withAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE, $this->callback(function($routeMiddleware) use ($route) {
                return $routeMiddleware instanceof RouteMiddleware &&
                    $routeMiddleware->route->name === $route->name;
            }))
            ->willReturn($requestWithRouteMiddleware);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($requestWithRouteMiddleware)
            ->willReturn($this->response);

        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);
        $result = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $result);
        $this->assertNotNull($middleware->matchedRoute);
        $this->assertEquals('api.users.show', $middleware->matchedRoute->name);
        $this->assertEquals('123', $middleware->matchedRoute->parameters->get('id'));
    }

    #[Test]
    #[TestDox('Performance test for route matching')]
    public function performance_test_for_route_matching(): void
    {
        $route = RouteRecord::get('perf.test', '/perf/test', 'PerfController');
        $this->routes->addRoute($route);

        $middleware = new MatchRouteMiddleware($this->middlewareFactory, $this->router);

        $this->setupRequestMockForMatching('/perf/test');

        $iterations = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $middleware->process($this->request, $this->handler);
        }

        $totalTime = microtime(true) - $startTime;

        // Should complete 1000 matches in reasonable time
        $this->assertLessThan(1.0, $totalTime,
            "Route matching performance is too slow: {$totalTime}s for {$iterations} iterations");
    }

    /**
     * Helper method to setup request mock for basic URI/method matching
     */
    private function setupRequestMock(string $uri, string $method = 'GET'): void
    {
        $this->uri = $this->createMock(UriInterface::class);
        $this->uri->method('__toString')->willReturn($uri);
        $this->request->method('getUri')->willReturn($this->uri);
        $this->request->method('getMethod')->willReturn($method);
    }

    /**
     * Helper method to setup request mock for route matching scenarios
     */
    private function setupRequestMockForMatching(string $uri, string $method = 'GET'): void
    {
        $this->setupRequestMock($uri, $method);

        // Setup mock for RouteMiddleware creation and attachment
        $requestWithRouteMiddleware = $this->createMock(ServerRequestInterface::class);
        $this->request->method('withAttribute')
            ->with(RouteMiddleware::REQUEST_ATTRIBUTE, $this->isInstanceOf(RouteMiddleware::class))
            ->willReturn($requestWithRouteMiddleware);

        $this->handler->method('handle')
            ->with($requestWithRouteMiddleware)
            ->willReturn($this->response);
    }
}