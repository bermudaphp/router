<?php

namespace Bermuda\Router\Middleware;

use Bermuda\Router\Exception\RouteNotFoundException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Route Not Found Handler with Dynamic Exception Mode
 *
 * Handles requests for routes that could not be matched in the application's routing table.
 * This handler serves as the final fallback in the request processing pipeline when no
 * matching route is found for the requested path and HTTP method combination.
 *
 * The handler supports two operational modes with flexible configuration:
 * 1. Exception mode: Throws RouteNotFoundException for upstream exception handling
 * 2. Response mode: Returns a structured JSON error response with 404 status
 *
 * Exception mode can be configured at construction time or dynamically via request attributes,
 * allowing different behavior for different requests within the same application instance.
 *
 * In response mode, provides detailed error information including the requested path,
 * HTTP method, and timestamp for better debugging and API consistency.
 * Supports custom error messages for better user experience and localization.
 */
final class RouteNotFoundHandler implements RequestHandlerInterface, MiddlewareInterface
{
    /**
     * Request attribute key for dynamically controlling exception mode.
     *
     * This attribute can be set on requests to override the default exception mode
     * behavior for specific requests, enabling per-request error handling strategies.
     */
    public const REQUEST_ATTRIBUTE_EXCEPTION_MODE = 'Bermuda\Router\Middleware\RouteNotFoundHandler::exceptionMode';

    /**
     * Initialize the route not found handler with configuration.
     *
     * @param ResponseFactoryInterface $responseFactory Factory for creating HTTP responses
     * @param bool|null $exceptionMode Global exception mode setting, null to use request attributes
     * @param string|null $customMessage Optional custom error message to override the default
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ?bool $exceptionMode = null,
        private readonly ?string $customMessage = null
    ) {}

    /**
     * Handle requests for non-existent routes with dynamic mode resolution.
     *
     * Processes requests that have reached this handler due to no matching routes
     * being found. The behavior depends on the resolved exception mode:
     *
     * Exception mode resolution priority:
     * 1. Constructor-defined mode (if not null)
     * 2. Request attribute mode (if present)
     * 3. Default to response mode (false)
     *
     * Exception mode: Throws RouteNotFoundException with request details, allowing
     * upstream middleware or exception handlers to manage the error response.
     *
     * Response mode: Creates a structured JSON error response containing:
     * - Error type and HTTP status code
     * - Human-readable error message (custom or default)
     * - Request details (path and method)
     * - RFC3339 formatted timestamp for logging
     *
     * @param ServerRequestInterface $request The unmatched HTTP request
     * @return ResponseInterface JSON error response with 404 status code
     * @throws RouteNotFoundException When exception mode is enabled
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $message = $this->customMessage ?? 'The requested endpoint was not found.';

        if ($this->getExceptionMode($request)) {
            RouteNotFoundException::throwForRequest($request);
        }

        $response = $this->responseFactory->createResponse(404);

        $error = [
            'error' => 'Not Found',
            'code' => 404,
            'message' => $message,
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339)
        ];

        $jsonBody = json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Ensure the response body is writable and write JSON content
        $body = $response->getBody();
        if ($body->isWritable()) {
            $body->write($jsonBody);
        }

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * Process the request as middleware (terminal behavior).
     *
     * When used as middleware, this component acts as a terminal handler and always
     * processes the request as a route not found scenario without calling the next
     * handler in the chain. This is typically used as the final middleware in a
     * pipeline to catch all unmatched requests.
     *
     * The exception mode is dynamically resolved based on constructor configuration
     * and request attributes, allowing different error handling strategies per request.
     *
     * @param ServerRequestInterface $request The HTTP request to process
     * @param RequestHandlerInterface $handler The next handler (not used in this implementation)
     * @return ResponseInterface 404 Not Found response or throws exception based on mode
     * @throws RouteNotFoundException When exception mode is enabled
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->handle($request);
    }

    /**
     * Add exception mode setting as a request attribute.
     *
     * Creates a new request instance with the exception mode preference stored as an attribute.
     * This allows dynamic control of error handling behavior on a per-request basis,
     * which is useful for different API endpoints or client types requiring different
     * error response formats.
     *
     * Follows PSR-7 immutability principles by returning a new request instance.
     *
     * @param ServerRequestInterface $request The request to modify
     * @param bool $mode True for exception mode, false for JSON response mode
     * @return ServerRequestInterface New request instance with exception mode attribute
     */
    public function withExceptionModeAttribute(ServerRequestInterface $request, bool $mode): ServerRequestInterface
    {
        return $request->withAttribute(self::REQUEST_ATTRIBUTE_EXCEPTION_MODE, $mode);
    }

    /**
     * Resolve the exception mode for the current request context.
     *
     * Determines whether to throw exceptions or return JSON responses based on
     * configuration precedence:
     *
     * 1. Constructor-defined mode takes highest priority (if not null)
     * 2. Request attribute mode is used if constructor mode is null
     * 3. Defaults to false (response mode) if neither is set
     *
     * This flexible resolution allows for global configuration with per-request overrides.
     *
     * @param ServerRequestInterface|null $request The request to check for attributes (required if constructor mode is null)
     * @return bool True for exception mode, false for JSON response mode
     */
    public function getExceptionMode(?ServerRequestInterface $request = null): bool
    {
        if ($this->exceptionMode !== null) {
            return $this->exceptionMode;
        }

        return (bool) $request?->getAttribute(self::REQUEST_ATTRIBUTE_EXCEPTION_MODE, false) ?? false;
    }
}