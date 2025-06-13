<?php

namespace Bermuda\Router\Exception;

/**
 * Base Router Exception
 *
 * Base exception class for all router-related errors and exceptional conditions.
 * This class serves as the foundation for more specific routing exceptions,
 * providing a common interface and inheritance hierarchy for router error handling.
 *
 * Extends RuntimeException to indicate that router exceptions represent runtime
 * errors that should typically be caught and handled gracefully rather than
 * being considered programming errors.
 *
 * Router Exception Hierarchy:
 * - RouterException (base class)
 *   ├── RouteNotFoundException (404 - route matching failures)
 *   ├── RouteNotRegisteredException (500 - route registration issues)
 *   └── GeneratorException (400 - URL generation failures)
 *
 * This hierarchy allows for granular exception handling where specific router
 * operations can catch targeted exceptions, while general router error handling
 * can catch the base RouterException to handle all router-related issues uniformly.
 *
 * Common usage patterns:
 * - Catch specific exceptions for targeted error handling
 * - Catch RouterException for general router error handling
 * - Use in middleware for centralized router exception processing
 * - Transform into appropriate HTTP responses based on exception type
 *
 * @package Bermuda\Router\Exception
 * @see RouteNotFoundException For route matching failures during request processing
 * @see RouteNotRegisteredException For route registration and configuration issues
 * @see GeneratorException For URL generation and parameter validation failures
 */
class RouterException extends \RuntimeException
{
    /**
     * Create exception for routes that are already registered.
     *
     * This method creates an exception for scenarios where an attempt is made
     * to register a route with a name that already exists in the route map.
     * Helps maintain route name uniqueness and prevents accidental overwrites.
     *
     * @param string $routeName The name of the route that is already registered
     * @param string|null $message Optional custom error message
     * @return self New RouterException instance for duplicate route registration
     */
    public static function forAlreadyRegisteredRoute(string $routeName, ?string $message = null): self
    {
        $defaultMessage = sprintf('Route "%s" is already registered in the route map', $routeName);

        return new self($message ?? $defaultMessage, 500);
    }

    /**
     * Create and immediately throw exception for already registered route.
     *
     * Convenience method that combines exception creation and throwing for
     * duplicate route registration scenarios. This method never returns normally.
     *
     * @param string $routeName The name of the route that is already registered
     * @param string|null $message Optional custom error message
     * @return never This method never returns - always throws an exception
     * @throws RouterException Always throws with details about the duplicate route
     */
    public static function throwForAlreadyRegisteredRoute(string $routeName, ?string $message = null): never
    {
        throw self::forAlreadyRegisteredRoute($routeName, $message);
    }

    /**
     * Throw exception if route with the given name is already registered.
     *
     * Checks if a route name already exists and throws an exception if it does.
     * This method is useful for validation before route registration to ensure
     * name uniqueness and prevent accidental route overwrites.
     *
     * If the route name is not found (doesn't exist), the method returns normally.
     * If the route name exists, throws a RouterException.
     *
     * @param string $routeName The route name to check for duplicates
     * @param callable $existsChecker Callback that returns true if route exists (e.g., $routeMap->has(...))
     * @param string|null $message Optional custom error message
     * @return void Returns normally if route doesn't exist
     * @throws RouterException If the route name is already registered
     */
    public static function throwIfAlreadyRegistered(string $routeName, callable $existsChecker, ?string $message = null): void
    {
        if ($existsChecker($routeName)) {
            self::throwForAlreadyRegisteredRoute($routeName, $message);
        }
    }
}
