<?php

namespace Bermuda\Router;

use Bermuda\Router\Exception\RouterException;

/**
 * Cached Routes Implementation
 *
 * High-performance route collection that operates with pre-compiled route data
 * for optimal performance in production environments. This class extends the base
 * Routes functionality while providing fast lookup through cached route data.
 *
 * The cache stores route information in a structured format with pre-compiled
 * regular expressions and metadata, eliminating the need for runtime compilation
 * during route matching operations.
 *
 * Architecture:
 * - Extends Routes class for hybrid functionality (cache + runtime routes)
 * - Uses PathExtractor trait for consistent URI path normalization
 * - Implements efficient two-tier lookup: runtime routes → cached routes
 * - Supports both static (O(1)) and dynamic (regex) route matching
 *
 * Cache Structure:
 * The cache is organized into two main sections for performance optimization:
 * - static: Routes without parameters for direct string comparison
 * - dynamic: Routes with parameters requiring regex pattern matching
 *
 * Each route entry contains complete route metadata:
 * - name: Unique route identifier for lookups and URL generation
 * - path: Original route pattern with parameter placeholders
 * - methods: Array of allowed HTTP methods (GET, POST, etc.)
 * - regex: Pre-compiled regular expression for URL matching
 * - handler: Route handler (controller class, callable, etc.)
 * - middleware: Middleware stack to execute before the handler
 * - group: Optional route group identifier for organization
 * - parameters: Parameter names extracted from the route pattern
 * - defaults: Default values for optional or missing parameters
 *
 * Performance Benefits:
 * - No runtime route compilation for cached routes
 * - Pre-computed regex patterns eliminate compilation overhead
 * - Optimized lookup strategies for different route types
 * - Memory-efficient iteration with lazy RouteRecord instantiation
 * - Fast path extraction with consistent normalization
 *
 * @template T of array{
 *  name: string,
 *  path: string,
 *  methods: array<string>,
 *  regex: string,
 *  handler: mixed,
 *  middleware: array<mixed>,
 *  group: ?string,
 *  parameters: array<string>,
 *  defaults: ?array<string, mixed>,
 * }
 *
 * @package Bermuda\Router
 */
final class RoutesCache extends Routes
{
    use PathExtractor;

    /**
     * Pre-compiled route cache organized by route type.
     *
     * Contains two arrays optimized for different matching strategies:
     * - static: Routes without parameters for O(1) string comparison
     * - dynamic: Routes with parameters requiring regex matching
     *
     * @var array{static: array<T>, dynamic: array<T>}
     */
    private array $cache;

    /**
     * Initialize RoutesCache with pre-compiled route data.
     *
     * Creates a new cached routes instance that combines pre-compiled route data
     * with the ability to add routes at runtime. The cache provides high-performance
     * lookups while maintaining full flexibility through the parent Routes class.
     *
     * @param array{static: array<T>, dynamic: array<T>} $cache Pre-compiled route cache data
     * @param CompilerInterface $compiler Route compiler for runtime route operations
     */
    public function __construct(
        array $cache,
        CompilerInterface $compiler = new Compiler,
    ) {
        $this->cache = $cache;
        parent::__construct($compiler);
    }

    /**
     * Add a new route with comprehensive duplicate checking.
     *
     * Extends the parent addRoute functionality with enhanced duplicate detection
     * that checks both cached routes and runtime-added routes. This prevents
     * accidental route overwrites and ensures route name uniqueness across
     * the entire collection.
     *
     * The method checks for existing routes using the hybrid lookup mechanism,
     * ensuring that both cached and runtime routes are considered during
     * duplicate detection.
     *
     * @param RouteRecord $route The route record to add to the collection
     * @return RoutesCache Returns this instance for method chaining
     * @throws RouterException If a route with the same name already exists
     */
    public function addRoute(RouteRecord $route): RoutesCache
    {
        if ($this->getRoute($route->name) !== null) {
            RouterException::throwForAlreadyRegisteredRoute($route->name);
        }

        parent::addRoute($route);
        return $this;
    }

    /**
     * Retrieve a route by name using hybrid lookup strategy.
     *
     * Implements a two-tier lookup mechanism that prioritizes runtime-added routes
     * over cached routes, allowing for dynamic route overrides while maintaining
     * fast cache-based lookups for the majority of routes.
     *
     * Lookup Strategy:
     * 1. Check runtime-added routes first (highest priority)
     * 2. Search through cached static routes
     * 3. Search through cached dynamic routes
     * 4. Return null if not found in any collection
     *
     * This approach ensures that newly added routes take precedence over cached
     * routes while maintaining optimal performance for cached route lookups.
     *
     * @param string $name Unique route name to search for
     * @return RouteRecord|null The route record if found, null otherwise
     */
    public function getRoute(string $name): ?RouteRecord
    {
        // Priority 1: Check runtime-added routes (they override cached routes)
        if (!empty($this->routes) && ($route = parent::getRoute($name)) !== null) {
            return $route;
        }

        // Priority 2: Search through cached routes (both static and dynamic)
        foreach ($this->cache as $routes) {
            foreach ($routes as $route) {
                if ($route['name'] === $name) {
                    return RouteRecord::fromArray($route);
                }
            }
        }

        return null;
    }

    /**
     * Match incoming HTTP requests against cached and runtime routes.
     *
     * High-performance route matching implementation that leverages pre-compiled
     * cache data for optimal performance. The method handles different RouteMap
     * types and implements efficient matching strategies based on route characteristics.
     *
     * Matching Strategy:
     * 1. Handle non-RoutesCache collections via delegation
     * 2. Normalize HTTP method and extract clean URI path
     * 3. Fast static route matching using direct string comparison
     * 4. Dynamic route matching using pre-compiled regex patterns
     * 5. Fallback to runtime route matching if no cache match found
     *
     * Parameter Extraction:
     * - Extracts named parameters from regex matches
     * - Applies default values for missing parameters
     * - Converts numeric strings to appropriate numeric types
     * - Handles optional parameters gracefully
     *
     * @param RouteMap $routes The route collection to match against
     * @param string $uri Complete request URI (path will be extracted)
     * @param string $requestMethod HTTP method of the request (case-insensitive)
     * @return RouteRecord|null Matched route with extracted parameters, or null
     */
    public function match(RouteMap $routes, string $uri, string $requestMethod): ?RouteRecord
    {
        $path = $this->extractPath($uri);

        // Handle non-RoutesCache collections by delegation
        if (!$routes instanceof RoutesCache) {
            if ($routes instanceof Matcher) $route = $routes->match($routes, $uri, $requestMethod);
            else $route = parent::match($routes, $uri, $requestMethod);

            return $route;
        }

        $requestMethod = HttpMethod::normalize($requestMethod);

        // Fast static route matching - O(1) lookup performance
        foreach ($routes->cache['static'] as $route) {
            if ($route['path'] === $path && in_array($requestMethod, $route['methods'])) {
                return RouteRecord::fromArray($route);
            }
        }

        // Dynamic route matching with regex patterns
        foreach ($routes->cache['dynamic'] as $route) {
            if (!in_array($requestMethod, $route['methods'])) continue;

            $parameters = _match($route['regex'], $path, $route['parameters'], $route['defaults'] ?? []);

            if ($parameters !== null) {
                $route['parameters'] = $parameters;

                return RouteRecord::fromArray($route);
            }
        }

        // Fallback to runtime route matching if cache didn't match
        if (!empty($this->routes)) {
            $route = parent::match($routes, $uri, $requestMethod);
            if ($route !== null) {
                return $route;
            }
        }

        return null;
    }


    /**
     * Get iterator over all routes (cached and runtime).
     *
     * Provides unified iteration over both cached routes and runtime-added routes.
     * The iterator creates RouteRecord instances on-demand to minimize memory usage
     * while maintaining a consistent interface across different route sources.
     *
     * Iteration Order:
     * 1. Cached static routes (fastest access)
     * 2. Cached dynamic routes (regex-based)
     * 3. Runtime-added routes (highest priority for lookups)
     *
     * The iterator yields RouteRecord objects, creating them lazily from cached
     * data to optimize memory usage during iteration operations.
     *
     * @return \Generator<RouteRecord> Iterator over all route records
     */
    public function getIterator(): \Generator
    {
        foreach ($this->cache['static'] as $routeData) {
            yield RouteRecord::fromArray($routeData);
        }

        foreach ($this->cache['dynamic'] as $routeData) {
            yield RouteRecord::fromArray($routeData);
        }

        // Runtime routes (already RouteRecord instances)
        if (!empty($this->routes)) {
            yield from $this->routes;
        }
    }

    /**
     * Export all routes to array format for serialization.
     *
     * Combines cached route data with runtime-added routes into a unified array
     * structure suitable for serialization, storage, or creating new RoutesCache
     * instances. The method preserves the cache structure while incorporating
     * any routes that were added at runtime.
     *
     * Export Strategy:
     * 1. Start with the original cached data structure
     * 2. Compile runtime-added routes to the same format
     * 3. Merge runtime routes into appropriate cache sections
     * 4. Return combined structure ready for re-caching
     *
     * The resulting array maintains the same structure as the input cache,
     * making it suitable for creating new RoutesCache instances or for
     * persistent storage in cache systems.
     *
     * @return array{static: array<T>, dynamic: array<T>} Combined route data
     */
    public function toArray(): array
    {
        // Don't duplicate cache data, just reference it
        if (empty($this->routes)) {
            return $this->cache; // No runtime routes, return cache as-is
        }

        // Only process runtime routes if they exist
        $runtimeArray = parent::toArray();

        return [
            'static' => [...$this->cache['static'], ...$runtimeArray['static']],
            'dynamic' => [...$this->cache['dynamic'], ...$runtimeArray['dynamic']]
        ];
    }
}