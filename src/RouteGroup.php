<?php

declare(strict_types=1);

namespace Bermuda\Router;

use Bermuda\Stdlib\Arrayable;

/**
 * Route group implementation for organizing and configuring related routes
 *
 * RouteGroup allows grouping related routes under a common path prefix, with shared
 * middleware and tokens. All routes added to the group will inherit the group's
 * configuration and the group name will be used as a prefix for route names.
 *
 * Uses mandatory callback function to communicate with parent Routes collection
 * for all route operations without direct dependency on RouteMap interface.
 *
 * Key changes:
 * - No direct RouteMap dependency - all operations via callback
 * - Group name ($name) is automatically used as route name prefix
 * - Simplified API without setNamePrefix/setPrefix methods
 * - All route updates handled through callback mechanism
 * - Middleware correctly applied to existing routes when changed
 *
 * @example
 * ```php
 * $routes = new Routes();
 * $apiGroup = $routes->group('api', '/api/v1');
 *
 * // Add routes - names will be prefixed with group name: 'api.users', 'api.posts'
 * $apiGroup->get('users', '/users', UserController::class);
 * $apiGroup->get('posts', '/posts', PostController::class);
 *
 * // Update group settings - callback will trigger automatic updates
 * $apiGroup->addMiddleware(AuthMiddleware::class)
 *          ->setTokens(['id' => '\d+']);
 * ```
 */
final class RouteGroup implements \IteratorAggregate, \Countable, Arrayable
{
    /**
     * Routes in this group indexed by final route name (with group prefix)
     *
     * @var array<string, RouteRecord>
     */
    private(set) array $routes = [];

    /**
     * Route tokens/patterns shared across group routes
     *
     * @var array<string, string>
     */
    private(set) array $tokens = [];

    /**
     * Middleware stack applied to all group routes
     *
     * @var array<mixed>
     */
    private(set) array $middleware = [];

    /**
     * Store original route data for each route (before group processing)
     * Used for proper route reconstruction when group settings change
     *
     * @var array<string, RouteRecord>
     */
    private array $originalRoutes = [];

    /**
     * Normalized path prefix for the group
     */
    public readonly string $pathPrefix;

    /**
     * Create a new route group
     *
     * The group name serves dual purpose:
     * 1. Unique group identifier
     * 2. Automatic prefix for all route names (e.g., 'api' → 'api.users')
     *
     * @param string $name Group name and route name prefix
     * @param string $pathPrefix URL path prefix for all routes in this group
     * @param \Closure $updateCallback Mandatory callback to handle all route operations
     *                                 Signature: function(string $operation, string $groupName, array $data): mixed
     */
    public function __construct(
        public readonly string $name,
        string $pathPrefix,
        private readonly \Closure $updateCallback
    ) {
        $this->pathPrefix = RoutePath::normalize($pathPrefix);
    }

    /**
     * Add middleware to the group's middleware stack
     *
     * Middleware will be prepended to each route's handler chain.
     * Triggers callback to notify about the change and update all existing routes.
     *
     * @param mixed $middleware Middleware to add (single middleware or array)
     * @return self Fluent interface
     *
     * @example
     * ```php
     * // Add single middleware
     * $group->addMiddleware(AuthMiddleware::class);
     *
     * // Add multiple middleware at once
     * $group->addMiddleware([
     *     AuthMiddleware::class,
     *     RateLimitMiddleware::class,
     *     CacheMiddleware::class
     * ]);
     * ```
     */
    public function addMiddleware(mixed $middleware): self
    {
        // Handle different middleware input types
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }

        $this->updateExistingRoutes();

        return $this;
    }

    /**
     * Set (replace) the entire middleware stack for the group
     *
     * @param array<mixed> $middleware Array of middleware handlers
     * @return self Fluent interface
     */
    public function setMiddleware(array $middleware): self
    {
        $this->middleware = $middleware;
        $this->updateExistingRoutes();
        return $this;
    }

    /**
     * Clear all middleware from the group
     *
     * @return self Fluent interface
     */
    public function clearMiddleware(): self
    {
        $this->middleware = [];
        $this->updateExistingRoutes();
        return $this;
    }

    /**
     * Set route tokens/patterns for all routes in this group
     *
     * Tokens define parameter patterns that will be merged with individual route tokens.
     * Group tokens have lower priority than route-specific tokens.
     * Triggers callback to notify about the change.
     *
     * @param array<string, string> $tokens Parameter name to pattern mapping
     * @return self Fluent interface
     *
     * @example
     * ```php
     * $group->setTokens([
     *     'id' => '\d+',
     *     'slug' => '[a-z0-9-]+',
     *     'version' => 'v\d+'
     * ]);
     * ```
     */
    public function setTokens(array $tokens): self
    {
        $this->tokens = $tokens;
        $this->updateExistingRoutes();
        return $this;
    }

    /**
     * Update all existing routes in the group with current group settings
     *
     * This method reprocesses all routes with current group configuration
     * and notifies the parent collection about the changes.
     */
    private function updateExistingRoutes(): void
    {
        if (empty($this->routes)) {
            return; // No routes to update
        }

        // Reprocess all routes with current group settings
        $updatedRoutes = [];

        foreach ($this->originalRoutes as $finalName => $originalRoute) {
            $processedRoute = $this->processRoute($originalRoute);
            $updatedRoutes[$processedRoute->name] = $processedRoute;

            // Update in local collection
            $this->routes[$processedRoute->name] = $processedRoute;
        }

        // Notify parent collection about updates via callback
        if (!empty($updatedRoutes)) {
            ($this->updateCallback)('routes_update', $this->name, [
                'updatedRoutes' => $updatedRoutes
            ]);
        }
    }

    /**
     * Add a route to this group with group configuration applied
     *
     * This method processes the route by:
     * 1. Storing original route data for future updates
     * 2. Applying group middleware to the route handler
     * 3. Merging group tokens with route tokens (route tokens take precedence)
     * 4. Prefixing the route name with the group name
     * 5. Combining the group path prefix with the route path
     * 6. Adding the processed route to the parent collection via callback
     *
     * @param RouteRecord $route The route to add to the group
     * @return self Fluent interface
     *
     * @example
     * ```php
     * $route = RouteRecord::get('users', '/users', UserController::class);
     * $group->addRoute($route);
     * // Results in route with path='/api/v1/users', name='api.users'
     * ```
     */
    public function addRoute(RouteRecord $route): self
    {
        $processedRoute = $this->processRoute($route);

        // Store original route for future updates
        $this->originalRoutes[$processedRoute->name] = $route;

        // Store in group collection
        $this->routes[$processedRoute->name] = $processedRoute;

        // Add to parent collection via callback
        ($this->updateCallback)('route_add', $this->name, [
            'route' => $processedRoute,
            'originalRoute' => $route
        ]);

        return $this;
    }

    /**
     * Convenience method to add a GET route
     *
     * @param string $name Route name (without group prefix)
     * @param string $path Route path (without group prefix)
     * @param mixed $handler Route handler
     * @return self Fluent interface
     */
    public function get(string $name, string $path, mixed $handler): self
    {
        return $this->addRoute(RouteRecord::get($name, $path, $handler));
    }

    /**
     * Convenience method to add a POST route
     *
     * @param string $name Route name (without group prefix)
     * @param string $path Route path (without group prefix)
     * @param mixed $handler Route handler
     * @return self Fluent interface
     */
    public function post(string $name, string $path, mixed $handler): self
    {
        return $this->addRoute(RouteRecord::post($name, $path, $handler));
    }

    /**
     * Convenience method to add a PUT route
     *
     * @param string $name Route name (without group prefix)
     * @param string $path Route path (without group prefix)
     * @param mixed $handler Route handler
     * @return self Fluent interface
     */
    public function put(string $name, string $path, mixed $handler): self
    {
        return $this->addRoute(RouteRecord::put($name, $path, $handler));
    }

    /**
     * Convenience method to add a PATCH route
     *
     * @param string $name Route name (without group prefix)
     * @param string $path Route path (without group prefix)
     * @param mixed $handler Route handler
     * @return self Fluent interface
     */
    public function patch(string $name, string $path, mixed $handler): self
    {
        return $this->addRoute(RouteRecord::patch($name, $path, $handler));
    }

    /**
     * Convenience method to add a DELETE route
     *
     * @param string $name Route name (without group prefix)
     * @param string $path Route path (without group prefix)
     * @param mixed $handler Route handler
     * @return self Fluent interface
     */
    public function delete(string $name, string $path, mixed $handler): self
    {
        return $this->addRoute(RouteRecord::delete($name, $path, $handler));
    }

    /**
     * Convenience method to add a HEAD route
     *
     * @param string $name Route name (without group prefix)
     * @param string $path Route path (without group prefix)
     * @param mixed $handler Route handler
     * @return self Fluent interface
     */
    public function head(string $name, string $path, mixed $handler): self
    {
        return $this->addRoute(RouteRecord::head($name, $path, $handler));
    }

    /**
     * Convenience method to add an OPTIONS route
     *
     * @param string $name Route name (without group prefix)
     * @param string $path Route path (without group prefix)
     * @param mixed $handler Route handler
     * @return self Fluent interface
     */
    public function options(string $name, string $path, mixed $handler): self
    {
        return $this->addRoute(RouteRecord::options($name, $path, $handler));
    }

    /**
     * Convenience method to add a route with any HTTP methods
     *
     * @param string $name Route name (without group prefix)
     * @param string $path Route path (without group prefix)
     * @param mixed $handler Route handler
     * @param array $methods HTTP methods
     * @return self Fluent interface
     */
    public function any(string $name, string $path, mixed $handler, array $methods = []): self
    {
        return $this->addRoute(RouteRecord::any($name, $path, $handler, $methods));
    }

    /**
     * Process a route with current group settings
     *
     * Applies all group configuration to the route:
     * - Prepends group middleware to route middleware stack
     * - Merges group tokens with route tokens (route tokens take precedence)
     * - Prefixes route name with group name
     * - Combines group path prefix with route path
     *
     * @param RouteRecord $route Original route
     * @return RouteRecord Processed route with group settings applied
     */
    private function processRoute(RouteRecord $route): RouteRecord
    {
        $combinedMiddleware = $this->middleware !== []
            ? [...$this->middleware, ...$route->middleware]
            : $route->middleware;

        // Merge group tokens with route tokens (route tokens take precedence)
        $routeTokens = $route->tokens->toArray();
        $combinedTokens = $this->tokens !== []
            ? array_merge($this->tokens, $routeTokens)
            : $routeTokens;

        // Apply group name as name prefix
        $prefixedName = $this->name . '.' . $route->name;

        // Combine group path prefix with route path
        $combinedPath = $this->combinePaths($this->pathPrefix, (string) $route->path);

        return new RouteRecord(
            $prefixedName,
            $combinedPath,
            $route->handler,
            $route->methods->toArray(),
            $combinedMiddleware,
            $combinedTokens,
            $route->parameters->toArray(),
            $route->defaults?->toArray(),
            $route->group ?? $this->name
        );
    }

    /**
     * Combine group path prefix with route path properly
     *
     * Ensures that paths are combined correctly by handling trailing/leading
     * slashes and normalizing the result.
     *
     * @param string $prefix Group path prefix
     * @param string $routePath Individual route path
     * @return string Combined and normalized path
     */
    private function combinePaths(string $prefix, string $routePath): string
    {
        // Handle root paths
        if ($routePath === '/' || $routePath === '') {
            return $prefix === '/' ? '/' : $prefix;
        }

        // Remove leading slash from route path if present
        $routePath = ltrim($routePath, '/');

        // Combine paths
        if ($prefix === '/') {
            $combined = '/' . $routePath;
        } else {
            $combined = rtrim($prefix, '/') . '/' . $routePath;
        }

        // Normalize the combined path
        return RoutePath::normalize($combined);
    }

    /**
     * Get processed routes for group updates
     *
     * Returns all routes in the group with current group settings applied.
     * Used by parent Routes collection during group configuration updates.
     *
     * @return array<string, RouteRecord> Processed routes indexed by final name
     * @internal Used by Routes class via callback mechanism
     */
    public function getProcessedRoutes(): array
    {
        $processedRoutes = [];

        foreach ($this->originalRoutes as $finalName => $originalRoute) {
            $processedRoutes[$finalName] = $this->processRoute($originalRoute);
        }

        return $processedRoutes;
    }

    /**
     * Get original routes before group processing
     *
     * @return array<string, RouteRecord> Original routes indexed by final name
     * @internal Used by Routes class for route reconstruction
     */
    public function getOriginalRoutes(): array
    {
        return $this->originalRoutes;
    }

    /**
     * Create a copy of this group for route collection cloning
     *
     * @param \Closure $newCallback New callback for the copied group
     * @return self New group instance with copied data
     * @internal Used by Routes::__clone()
     */
    public static function copy(RouteGroup $group, \Closure $newCallback): self
    {
        $copy = new self($group->name, $group->pathPrefix, $newCallback);
        $copy->middleware = $group->middleware;
        $copy->tokens = $group->tokens;

        // Copy routes and original routes
        foreach ($group->routes as $name => $route) {
            $copy->routes[$name] = clone $route;
        }

        foreach ($group->originalRoutes as $name => $originalRoute) {
            $copy->originalRoutes[$name] = clone $originalRoute;
        }

        return $copy;
    }

    /**
     * Get iterator over all routes in this group
     *
     * @return \Generator<string, RouteRecord> Iterator over group routes
     */
    public function getIterator(): \Generator
    {
        yield from $this->routes;
    }

    /**
     * Get the number of routes in this group
     *
     * @return int Number of routes in the group
     */
    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * Check if the group has any routes
     *
     * @return bool True if group has routes, false otherwise
     */
    public function isEmpty(): bool
    {
        return empty($this->routes);
    }

    /**
     * Check if a specific route exists in this group
     *
     * @param string $name Final route name (with group prefix) to check
     * @return bool True if route exists in group
     */
    public function hasRoute(string $name): bool
    {
        return isset($this->routes[$name]);
    }

    /**
     * Get a specific route from this group
     *
     * @param string $name Final route name (with group prefix)
     * @return RouteRecord|null Route if found, null otherwise
     */
    public function getRoute(string $name): ?RouteRecord
    {
        return $this->routes[$name] ?? null;
    }

    /**
     * Get group configuration as array
     *
     * @return array{
     *     name: string,
     *     pathPrefix: string,
     *     middleware: array,
     *     tokens: array,
     *     routeCount: int
     * } Group configuration
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'pathPrefix' => $this->pathPrefix,
            'middleware' => $this->middleware,
            'tokens' => $this->tokens,
            'routeCount' => $this->count()
        ];
    }
}