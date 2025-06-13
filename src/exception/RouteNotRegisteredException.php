<?php

namespace Bermuda\Router\Exception;

use Bermuda\Router\RouteMap;

/**
 * Route Not Registered Exception
 *
 * Exception thrown when attempting to access or reference a route that has not been
 * registered in the application's route map. This typically occurs when trying to
 * generate URLs, retrieve route information, or perform operations on routes that
 * don't exist in the routing configuration.
 *
 * This exception helps distinguish between routes that exist but don't match a request
 * (RouteNotFoundException) and routes that simply haven't been defined at all.
 *
 * Common scenarios:
 * - URL generation for non-existent named routes
 * - Route retrieval by name when route doesn't exist
 * - Accessing route metadata for unregistered routes
 */
final class RouteNotRegisteredException extends RouterException
{
    /**
     * Initialize the route not registered exception.
     *
     * @param string $routeName The name of the route that was not found in registration
     * @param string|null $message Optional custom error message
     */
    public function __construct(
        public readonly string $routeName,
        ?string $message = null
    ) {
        parent::__construct(
            $message ?? sprintf('Route "%s" is not registered in the route map', $this->routeName),
            500 // Internal server error as this indicates configuration issue
        );
    }

    /**
     * Create exception for a specific route name.
     *
     * @param string $routeName The name of the unregistered route
     * @param string|null $message Optional custom error message
     * @return self New exception instance
     */
    public static function forRoute(string $routeName, ?string $message = null): self
    {
        return new self($routeName, $message);
    }

    /**
     * Create and immediately throw exception for a specific route name.
     *
     * @param string $routeName The name of the unregistered route
     * @param string|null $message Optional custom error message
     * @return never This method never returns - always throws an exception
     * @throws RouteNotRegisteredException Always throws with the specified route name
     */
    public static function throwForRoute(string $routeName, ?string $message = null): never
    {
        throw self::forRoute($routeName, $message);
    }

}