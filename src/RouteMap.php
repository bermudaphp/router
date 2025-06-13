<?php

namespace Bermuda\Router;

/**
 * Route Map Interface
 *
 * Defines the contract for route collections that store and manage application routes.
 * A RouteMap serves as a registry of all defined routes, providing methods for
 * route registration, retrieval, and hierarchical organization through route groups.
 *
 * @package Bermuda\Router
 * @extends \IteratorAggregate<int, RouteRecord>
 */
interface RouteMap extends Cacheable, \IteratorAggregate
{
    /**
     * Retrieve a route by its name.
     *
     * @param string $name The unique name of the route to retrieve
     * @return RouteRecord|null The route record if found, null if no route exists with the given name
     */
    public function getRoute(string $name): ?RouteRecord;

    /**
     * Add a route to the route map.
     *
     * Registers a new route in the map, making it available for matching and
     * URL generation. Each route must have a unique name within the route map
     * to ensure predictable behavior and avoid naming conflicts.
     *
     * The route is added with priority based on registration order - routes
     * registered earlier will have higher priority during matching.
     *
     * Returns the RouteMap instance to enable method chaining for fluent
     * route registration patterns.
     *
     * @param RouteRecord $route The route record to add to the map
     * @return RouteMap Returns this instance for method chaining
     * @throws Exception\RouterException If a route with the same name is already registered in the map
     */
    public function addRoute(RouteRecord $route): RouteMap;

    /**
     * Create or retrieve a route group for organizing related routes.
     *
     * Behavior depends on the provided parameters:
     * - If prefix is null: returns existing group by name or throws exception if group doesn't exist
     * - If both parameters provided: registers a new route group in RouteMap and returns it
     *
     * Groups enable logical organization of routes with shared configuration such as
     * URL prefixes, middleware, or namespaces.
     *
     * @param string $name The unique name of the route group
     * @param string|null $prefix Optional URL prefix to apply to all routes in the group.
     *                           If null, retrieves existing group by name.
     * @return RouteGroup The route group instance for adding routes
     * @throws Exception\RouterException If group retrieval fails when prefix is null,
     *                                  or if group creation fails when prefix is provided
     */
    public function group(string $name, ?string $prefix = null): RouteGroup;

    /**
     * Get an iterator for traversing all routes in priority order.
     *
     * Returns an iterator that yields routes sorted by their priority for optimal
     * matching performance. Higher priority routes are yielded first, ensuring
     * that more specific or important routes are matched before generic ones.
     *
     * Route priority is determined by the order of addition to the collection:
     * routes added earlier have higher priority and are returned first by the iterator.
     * This "first registered, first matched" principle ensures predictable routing
     * behavior and allows developers to control route precedence through registration order.
     *
     * Priority-based ordering is crucial for correct route matching behavior,
     * especially when dealing with overlapping route patterns where the order
     * of evaluation determines which route gets matched first.
     *
     * @return \Traversable<RouteRecord> Iterator over all route records sorted by registration order (priority)
     */
    public function getIterator(): \Traversable;
}