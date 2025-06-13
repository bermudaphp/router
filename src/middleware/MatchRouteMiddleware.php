<?php

namespace Bermuda\Router\Middleware;

use Bermuda\Router\Router;
use Bermuda\Router\RouteRecord;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;

/**
 * Route Matching Middleware
 *
 * Responsible for matching incoming HTTP requests against the application's route definitions.
 * This middleware sits early in the request processing pipeline and determines which route
 * (if any) should handle the current request based on the URL path and HTTP method.
 *
 * When a route is successfully matched:
 * - Route parameters are extracted and added as request attributes
 * - A RouteMiddleware instance is created and attached to the request
 * - Request processing continues with route-specific handling
 *
 * When no route matches:
 * - Request processing continues to the next handler (typically RouteNotFoundHandler)
 * - No route information is attached to the request
 *
 * This middleware maintains state about the last matched route for debugging purposes.
 */
final class MatchRouteMiddleware implements MiddlewareInterface
{
    /**
     * The route that was matched by the most recent request, if any.
     * Useful for debugging and introspection purposes.
     */
    private(set) ?RouteRecord $matchedRoute = null;

    /**
     * Initialize the route matching middleware with dependencies.
     *
     * @param MiddlewareFactoryInterface $middlewareFactory Factory for creating middleware from route handlers
     * @param Router $router Router instance containing all registered routes
     */
    public function __construct(
        private readonly MiddlewareFactoryInterface $middlewareFactory,
        private readonly Router $router
    ) {
    }

    /**
     * Process the request through route matching logic.
     *
     * Attempts to match the incoming request against registered routes using the router.
     * If a matching route is found:
     * 1. Stores the matched route for later access
     * 2. Extracts route parameters and adds them as request attributes
     * 3. Creates a RouteMiddleware instance and attaches it to the request
     * 4. Continues processing with the modified request
     *
     * If no route matches, passes the request to the next handler unchanged,
     * typically resulting in a 404 Not Found response from a downstream handler.
     *
     * @param ServerRequestInterface $request The HTTP request to match against routes
     * @param RequestHandlerInterface $handler The next handler in the middleware chain
     * @return ResponseInterface The HTTP response from route processing or next handler
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->matchedRoute = $this->router->match($request->getUri()->__toString(), $request->getMethod());

        if (!$this->matchedRoute) {
            return $handler->handle($request);
        }

        // Add route parameters as request attributes
        foreach ($this->matchedRoute->parameters as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        // Create RouteMiddleware and attach it to the request, then continue processing
        return $handler->handle(
            RouteMiddleware::createAndAttachToRequest($request, $this->middlewareFactory, $this->matchedRoute)
        );
    }

    /**
     * Create MatchRouteMiddleware instance from dependency injection container.
     *
     * Factory method for creating the middleware when using dependency injection containers.
     * Automatically resolves the required dependencies (MiddlewareFactoryInterface and Router)
     * from the container and creates a properly configured instance.
     *
     * @param ContainerInterface $container DI container with required dependencies
     * @return MatchRouteMiddleware Configured middleware instance ready for use
     */
    public static function createFromContainer(ContainerInterface $container): MatchRouteMiddleware
    {
        return new MatchRouteMiddleware(
            $container->get(MiddlewareFactoryInterface::class),
            $container->get(Router::class)
        );
    }
}