<?php

namespace Bermuda\Router\Exception;

use Bermuda\Router\HttpMethod;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Route Not Found Exception
 *
 * Specialized exception thrown when a requested route cannot be found or matched
 * in the application's routing configuration. This exception provides detailed
 * information about the failed route lookup, including the requested path and
 * HTTP method, making it easier to debug routing issues.
 *
 * The exception automatically sets the HTTP status code to 404 (Not Found)
 * and provides factory methods for convenient creation from PSR-7 request objects.
 */
final class RouteNotFoundException extends RouterException
{
    /**
     * Initialize the route not found exception with request details.
     *
     * Creates an exception containing information about the route that could not be found.
     * If no custom message is provided, generates a default message describing the
     * failed route lookup with the HTTP method and path.
     *
     * @param string $path The requested path that could not be matched
     * @param string $requestMethod The HTTP method used in the request
     * @param string|null $message Optional custom error message, defaults to auto-generated message
     */
    public function __construct(
        public readonly string $path,
        public readonly string $requestMethod,
        ?string $message = null
    ) {
        parent::__construct(
            $message ?? sprintf('Route not found for %s %s', $this->requestMethod, $this->path),
            404
        );
    }

    /**
     * Create exception instance from a PSR-7 request object.
     *
     * Factory method that extracts the path and HTTP method from a ServerRequest
     * and creates a RouteNotFoundException with that information. The HTTP method
     * is normalized using the HttpMethod utility to ensure consistent formatting.
     *
     * @param ServerRequestInterface $request The HTTP request that failed to match any route
     * @param string|null $msg Optional custom error message to override the default message
     * @return self New RouteNotFoundException instance with request details
     */
    public static function createFromRequest(ServerRequestInterface $request, ?string $msg = null): self
    {
        return new self($request->getUri()->getPath(), HttpMethod::normalize($request->getMethod()), $msg);
    }

    /**
     * Create and immediately throw exception for the given request.
     *
     * Convenience method that combines exception creation and throwing into a single
     * operation. This method never returns normally - it always throws an exception.
     * Supports custom error messages for better error reporting and localization.
     *
     * @param ServerRequestInterface $request The HTTP request that failed to match any route
     * @param string|null $msg Optional custom error message to override the default message
     * @return never This method never returns - always throws an exception
     * @throws RouteNotFoundException Always throws with details from the request
     */
    public static function throwForRequest(ServerRequestInterface $request, ?string $msg = null): never
    {
        throw self::createFromRequest($request, $msg);
    }
}