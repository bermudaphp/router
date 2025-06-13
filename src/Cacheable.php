<?php

namespace Bermuda\Router;

use Bermuda\Stdlib\Arrayable;

/**
 * Cacheable Interface
 *
 * Defines the contract for objects that can be serialized to arrays for caching purposes.
 * This interface enables route collections and related objects to export their data
 * in a format suitable for persistent storage and fast reconstruction.
 *
 * The caching mechanism improves application performance by avoiding the overhead
 * of route compilation and processing on every request.
 */
interface Cacheable extends Arrayable
{
    /**
     * Export route group data to array for caching purposes.
     *
     * Converts the route collection into a structured array format that can be
     * easily serialized, cached, and later reconstructed. The method separates
     * routes into static and dynamic categories for optimized matching performance.
     *
     * Static routes have fixed paths without parameters, while dynamic routes
     * contain parameters and require pattern matching during request processing.
     *
     * @template T of array{
     *     name: string,
     *     path: string,
     *     methods: array<string>,
     *     regex: string,
     *     handler: mixed,
     *     middleware: array<mixed>,
     *     group: string,
     *     parameters: array,
     *     defaults: ?array,
     * }
     * @return array{static: T, dynamic: T} Array containing static and dynamic route definitions
     */
    public function toArray(): array;
}