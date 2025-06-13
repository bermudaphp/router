<?php

namespace Bermuda\Router\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Bermuda\Router\Exception\RouteNotFoundException;

/**
 * Route Dispatch Middleware
 *
 * This middleware is responsible for executing matched routes in the application.
 * It works in conjunction with MatchRouteMiddleware which attaches RouteMiddleware
 * to the request when a route is successfully matched.
 *
 * Processing Flow:
 * 1. Checks if a RouteMiddleware has been attached to the request (by MatchRouteMiddleware)
 * 2. If found, executes the route through its middleware chain
 * 3. If no route found but fallback handler provided, uses the fallback handler
 * 4. If neither route nor fallback available, throws RouteNotFoundException
 *
 * This middleware typically sits after MatchRouteMiddleware in the middleware stack
 * and handles the actual execution of matched routes or fallback behavior.
 */
final class DispatchRouteMiddleware implements MiddlewareInterface
{
    /**
     * Initialize the dispatch middleware with optional fallback handler.
     *
     * The fallback handler is used when no route was matched by the router.
     * This is useful for implementing custom 404 handling, API error responses,
     * or any other fallback behavior instead of throwing an exception.
     *
     * @param RequestHandlerInterface|null $handler Optional fallback handler for unmatched requests
     */
    public function __construct(
        private ?RequestHandlerInterface $handler = null
    ) {
    }

    /**
     * Process the request through route dispatch logic.
     *
     * This method implements the core dispatching logic:
     *
     * 1. **Route Execution**: If a RouteMiddleware was attached to the request
     *    (indicating a successful route match), it executes the route's middleware
     *    chain which includes any route-specific middlewares and the final handler.
     *
     * 2. **Fallback Handling**: If no route was matched but a fallback handler
     *    was provided in the constructor, it delegates request handling to the
     *    fallback handler. This allows for custom 404 pages or API error responses.
     *
     * 3. **Exception Throwing**: If neither a route nor fallback handler is available,
     *    it throws a RouteNotFoundException with details about the unmatched request.
     *
     * @param ServerRequestInterface $request The HTTP request to dispatch
     * @param RequestHandlerInterface $handler The next handler in the middleware chain
     * @return ResponseInterface The HTTP response from route execution or fallback handling
     * @throws RouteNotFoundException When no route matches and no fallback handler is available
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $route = RouteMiddleware::fromRequest($request);

        if ($route) return $route->process($request, $handler);
        if ($this->handler) return $this->handler->handle($request);

        RouteNotFoundException::throwForRequest($request);
    }
}