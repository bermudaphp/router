<?php

namespace Bermuda\Router\Locator;

use Bermuda\Router\RouteMap;

/**
 * Route Locator Interface
 *
 * Defines a contract for classes responsible for locating and retrieving application routing configurations.
 *
 * This interface establishes a standardized way to access route definitions from various sources
 * such as configuration files, databases, or other storage mechanisms. Implementations should
 * handle the loading, parsing, and compilation of route definitions into a unified RouteMap object.
 *
 * The interface supports different routing strategies including:
 * - File-based route configuration
 * - Cached route loading for performance optimization
 */
interface RouteLocatorInterface
{
    /**
     * Retrieves the complete routing map for the application.
     *
     * This method is responsible for loading, processing, and returning all registered routes
     * in the application. The returned RouteMap object contains all route definitions that
     * have been configured, compiled, and are ready for use by the routing system.
     *
     * Implementations may:
     * - Load routes from configuration files
     *
     * @return RouteMap A complete map containing all registered application routes
     *
     * @throws RouterException If routes cannot be loaded or processed
     */
    public function getRoutes(): RouteMap;
}
