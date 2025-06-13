<?php

namespace Bermuda\Router\Middleware;

use Bermuda\Pipeline\PipelineFactory;
use Bermuda\Pipeline\PipelineFactoryInterface;
use Bermuda\Router\RouteRecord;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Bermuda\Router\Exception\ExceptionFactory;
use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;

/**
 * Route Middleware - Handles route execution within the middleware stack.
 *
 * This middleware is responsible for processing individual routes by creating and executing
 * the appropriate middleware for a matched route. It acts as both middleware and request handler,
 * allowing it to be used in different contexts within the request processing pipeline.
 *
 * The middleware integrates with a middleware factory to create handlers from route definitions
 * and provides request attribute functionality for accessing route information in subsequent middleware.
 */
final class RouteMiddleware implements MiddlewareInterface, RequestHandlerInterface
{
    /**
     * Request attribute key for storing the RouteMiddleware instance.
     *
     * This constant defines the key used to store this middleware instance
     * as a request attribute, allowing other middleware to access route information.
     */
    public const string REQUEST_ATTRIBUTE = RouteMiddleware::class;

    /**
     * Initialize the route middleware with dependencies.
     *
     * @param MiddlewareFactoryInterface $middlewareFactory Factory for creating middleware from route handlers
     * @param RouteRecord $route The matched route record containing handler and metadata
     */
    public function __construct(
        private readonly MiddlewareFactoryInterface $middlewareFactory,
        public readonly RouteRecord $route,
        private readonly PipelineFactoryInterface $pipelineFactory = new PipelineFactory()
    ) {
    }

    /**
     * Handle the request as a standalone request handler.
     *
     * This method allows the RouteMiddleware to act as a terminal request handler
     * when no additional handlers are needed in the pipeline. It creates a fallback
     * handler that throws an exception if reached, ensuring proper error handling.
     *
     * @param ServerRequestInterface $request The HTTP request to handle
     * @return ResponseInterface The HTTP response
     * @throws \RuntimeException If the fallback handler is reached (should not happen in normal flow)
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $req): ResponseInterface
            {
                throw new \RuntimeException('Empty request handler');
            }
        });
    }

    /**
     * Process the request through the route's middleware pipeline.
     *
     * Creates middleware from the route's pipeline using the middleware factory.
     * The pipeline contains the complete chain of middleware and handler configured
     * for this specific route. This method executes the route's middleware stack
     * and business logic in the proper order.
     *
     * @param ServerRequestInterface $request The HTTP request to process
     * @param RequestHandlerInterface $handler The next handler in the middleware chain
     * @return ResponseInterface The HTTP response from the route pipeline
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pipeline = $this->route->pipeline;

        if (count($pipeline) > 1) {
            $pipeline = $this->pipelineFactory->createMiddlewarePipeline($pipeline);
        } else $pipeline = $pipeline[0];

        return $this->middlewareFactory->makeMiddleware($pipeline)
            ->process($request, $handler);
    }

    /**
     * Add this middleware instance as a request attribute.
     *
     * Returns a new request instance with this RouteMiddleware added as an attribute.
     * This allows subsequent middleware to access route information and metadata.
     * Follows PSR-7 immutability principles by returning a new request instance.
     *
     * @param ServerRequestInterface $request The request to modify
     * @return ServerRequestInterface New request instance with the route middleware attribute
     */
    public function withRouteAttribute(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withAttribute(self::REQUEST_ATTRIBUTE, $this);
    }

    /**
     * Create RouteMiddleware instance and attach it to the request as an attribute.
     *
     * This factory method combines instance creation and request attribute setting
     * into a single operation. It creates a new RouteMiddleware instance with the
     * provided dependencies and immediately attaches it to the request as an attribute,
     * making route information available to subsequent middleware in the pipeline.
     *
     * @param ServerRequestInterface $request The HTTP request to attach the middleware to
     * @param MiddlewareFactoryInterface $middlewareFactory Factory for creating middleware from route handlers
     * @param RouteRecord $route The matched route record containing handler and metadata
     * @return ServerRequestInterface New request instance with the RouteMiddleware attached as an attribute
     */
    public static function createAndAttachToRequest(
        ServerRequestInterface $request,
        MiddlewareFactoryInterface $middlewareFactory,
        RouteRecord $route
    ): ServerRequestInterface {
        return new self($middlewareFactory, $route)->withRouteAttribute($request);
    }

    /**
     * Extract RouteMiddleware instance from request attributes.
     *
     * Retrieves the RouteMiddleware instance that was previously stored as a request
     * attribute. This allows other middleware or handlers to access route information
     * and metadata during request processing.
     *
     * @param ServerRequestInterface $request The request containing the route attribute
     * @return RouteMiddleware|null The RouteMiddleware instance if found, null otherwise
     */
    public static function fromRequest(ServerRequestInterface $request): ?self
    {
        $attribute = $request->getAttribute(self::REQUEST_ATTRIBUTE);

        return $attribute instanceof self ? $attribute : null;
    }

    /**
     * Extract RouteMiddleware instance from request attributes with exception on failure.
     *
     * Similar to fromRequest() but throws an exception if the RouteMiddleware
     * is not found in the request attributes. Useful when route information
     * is required and its absence indicates an error condition.
     *
     * @param ServerRequestInterface $request The request containing the route attribute
     * @return RouteMiddleware The RouteMiddleware instance
     * @throws \RuntimeException If RouteMiddleware is not found in request attributes
     */
    public static function fromRequestOrFail(ServerRequestInterface $request): self
    {
        $routeMiddleware = self::fromRequest($request);

        if ($routeMiddleware === null) {
            throw new \RuntimeException(
                'RouteMiddleware not found in request attributes. Ensure the request has been processed through routing middleware.'
            );
        }

        return $routeMiddleware;
    }
}
