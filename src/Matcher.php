<?php

namespace Bermuda\Router;

use Bermuda\Router\Exception\MatchException;

/**
 * Route Matcher Interface
 *
 * Defines the contract for matching incoming HTTP requests against registered routes.
 * Implementations are responsible for determining which route (if any) should handle
 * a specific request based on the request URI and HTTP method.
 *
 * The matcher performs the core routing logic by comparing request URIs against
 * route patterns, validating HTTP method compatibility, and extracting route parameters.
 *
 * Error Handling:
 * Implementations should throw MatchException when encountering errors during the
 * matching process, such as malformed regex patterns, parameter extraction failures,
 * or other matching-related issues. This provides detailed context for debugging
 * routing problems.
 *
 * @package Bermuda\Router
 */
interface Matcher
{
    /**
     * Match a request against the available routes.
     *
     * Attempts to find a route that matches the provided URI and HTTP method.
     * The matcher automatically extracts the path component from the URI,
     * ignoring query parameters, fragments, and other URI components.
     *
     * If a matching route is found, returns a RouteRecord with populated
     * parameter values. If no route matches, returns null.
     *
     * Matching Process:
     * 1. Extract clean path from URI (removing query strings, fragments)
     * 2. Normalize HTTP method for consistent comparison
     * 3. Test routes in priority order (registration order)
     * 4. Apply regex patterns for parametrized routes
     * 5. Extract and convert parameter values
     * 6. Return matched route with populated parameters
     *
     * Error Handling:
     * The matcher should handle errors gracefully and provide meaningful
     * error information through MatchException when problems occur:
     * - Malformed regex patterns in route definitions
     * - Parameter extraction or type conversion failures
     * - PCRE errors during pattern matching
     * - Other unexpected matching errors
     *
     * @param RouteMap $routes The collection of routes to match against
     * @param string $uri The complete request URI (path will be extracted automatically)
     * @param string $requestMethod The HTTP method of the request (GET, POST, etc.)
     * @return RouteRecord|null The matched route with extracted parameters, or null if no match found
     * @throws MatchException When route pattern matching encounters errors or fails due to malformed patterns
     *
     * @example
     * ```php
     * try {
     *     $route = $matcher->match($routes, '/api/users/123', 'GET');
     *     if ($route) {
     *         // Route matched successfully
     *         $userId = $route->parameters->get('id'); // 123 (converted to int)
     *     } else {
     *         // No matching route found - handle 404
     *     }
     * } catch (MatchException $e) {
     *     // Handle matching errors (malformed patterns, etc.)
     *     error_log("Route matching error: {$e->getMessage()}");
     *     error_log("Failed pattern: {$e->pattern}");
     *     error_log("Requested path: {$e->path}");
     * }
     * ```
     */
    public function match(RouteMap $routes, string $uri, string $requestMethod): ?RouteRecord;
}