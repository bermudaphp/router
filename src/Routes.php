<?php

declare(strict_types=1);

namespace Bermuda\Router;

use Bermuda\Router\Exception\RouteNotRegisteredException;
use Bermuda\VarExport\VarExporter;
use Psr\Container\ContainerInterface;
use Bermuda\Router\Exception\RouterException;
use Bermuda\Router\Exception\GeneratorException;

use function Bermuda\VarExport\export_array;
use function Bermuda\VarExport\export_var;

/**
 * Main route collection and matching implementation
 *
 * Handles route registration, matching against requests, and URL generation
 * using the Compiler for route pattern compilation. This class serves as the
 * central registry for all routes in an application and provides efficient
 * matching algorithms optimized for both static and dynamic routes.
 *
 * Key Features:
 * - Route registration with automatic categorization (static vs dynamic)
 * - Fast route matching with optimized lookup strategies
 * - URL generation from route names and parameters
 * - Route grouping with shared configuration (middleware, path prefixes, tokens)
 * - Immutable route updates with position preservation
 * - Callback-based group communication without tight coupling
 *
 * Route Storage Architecture:
 * - Static routes: Direct path → route name mapping for O(1) lookup
 * - Dynamic routes: Pattern-based matching with regex compilation
 * - Route groups: Organized collections with shared settings via callbacks
 * - Group data tracking: Original route data for intelligent updates
 *
 * Performance Optimizations:
 * - Static route fast-path bypass regex matching entirely
 * - Prefix-based group filtering reduces matching attempts
 * - Smart update strategies preserve route order and minimize work
 *
 * Group Communication:
 * Route groups communicate with this collection via callback functions only,
 * eliminating direct dependencies while maintaining full functionality.
 * All group operations (add routes, update middleware, etc.) are handled
 * through the callback mechanism for clean separation of concerns.
 *
 * @package Bermuda\Router
 */
class Routes implements RouteMap, Matcher, Generator
{
    use PathExtractor;

    /**
     * Route mapping storage separated by type for optimization.
     *
     * Routes are categorized into static and dynamic for performance optimization:
     * - Static routes: No parameters, use direct string comparison (fastest)
     * - Dynamic routes: Have parameters, require regex matching (slower but flexible)
     *
     * Each category maps route paths to route names. When multiple routes share
     * the same path (different HTTP methods), the value becomes an array of route names.
     *
     * Structure:
     * - static: ['/users' => 'users.index', '/about' => 'about']
     * - dynamic: ['/users/[id]' => 'users.show', '/posts/[slug]' => 'posts.show']
     * - Multiple: ['/api/data' => ['api.get', 'api.post']] for different methods
     *
     * @var array{static: array<string, string|array<string>>, dynamic: array<string, string|array<string>>}
     */
    protected array $map = [
        'static' => [],
        'dynamic' => [],
    ];

    /**
     * All registered routes indexed by unique name.
     *
     * Master registry of all routes in the collection. Each route must have a
     * unique name that serves as the primary key for lookups and URL generation.
     *
     * This collection maintains insertion order, which is important for route
     * matching precedence and group management. When routes are updated due to
     * group changes, their positions are preserved through smart update strategies.
     *
     * Route names follow common patterns:
     * - Simple: 'home', 'about', 'contact'
     * - Hierarchical: 'api.users.show', 'admin.posts.edit'
     * - Action-based: 'user-create', 'post-update', 'file-delete'
     *
     * @var array<string, RouteRecord> Route name => RouteRecord mapping
     */
    protected array $routes = [];

    /**
     * Route groups collection for organized route management.
     *
     * Groups enable shared configuration across multiple routes including:
     * - Common path prefixes (/api/v1, /admin, /public)
     * - Shared middleware stacks (auth, cors, rate limiting)
     * - Consistent naming patterns (group name as route name prefix)
     * - Token patterns for parameter validation
     *
     * Each group communicates with this collection via callback functions only,
     * ensuring loose coupling while maintaining full functionality. Groups
     * automatically handle route processing and communicate changes through
     * their callback mechanism.
     *
     * Groups support:
     * - Automatic route processing during addition
     * - Live configuration updates with route synchronization
     * - Middleware stack management
     * - Path and name prefix application
     *
     * @var array<string, RouteGroup> Group name => RouteGroup mapping
     */
    protected array $groups = [];

    /**
     * Store original route data before group processing for reconstruction.
     *
     * When routes are added to groups, their original configuration is preserved
     * here before group settings (middleware, path prefixes, tokens) are applied.
     * This enables intelligent updates when group configuration changes.
     *
     * Data structure per route:
     * - groupName: Which group the route belongs to
     * - originalData: Route configuration before group processing
     *
     * This mechanism enables:
     * - Accurate route reconstruction when group settings change
     * - Separation of route-specific vs group-inherited configuration
     * - Intelligent middleware management with position preservation
     * - Clean group configuration updates without side effects
     *
     * Use cases:
     * - Group middleware changes: Reconstruct routes with new middleware stack
     * - Path prefix updates: Rebuild paths with new group prefix
     * - Token changes: Update parameter validation patterns
     * - Group name changes: Rebuild route names with new prefix
     *
     * @var array<string, array{groupName: string, originalData: array}> Route name => group data mapping
     */
    protected array $routeGroupData = [];

    /**
     * Fast lookup index for static routes by HTTP method
     */
    private array $staticRouteIndex = [];

    /**
     * Prefix tree for efficient group matching
     */
    private array $groupPrefixTree = [];

    /**
     * Flag to track if indexes need rebuilding
     */
    private bool $indexesDirty = true;

    /**
     * Create a new Routes instance with specified compiler.
     *
     * Initializes the route collection with a compiler responsible for transforming
     * route patterns with square bracket parameters into efficient regular expressions.
     * The compiler handles parameter extraction, pattern validation, and URL generation.
     *
     * The default Compiler supports:
     * - Square bracket parameter syntax: [name], [?name], [name:pattern]
     * - Token-based pattern validation with customizable defaults
     * - Optional parameter handling with smart slash management
     * - URL generation with parameter substitution
     *
     * @param CompilerInterface $compiler Route pattern compiler (defaults to standard Compiler)
     */
    public function __construct(
        protected CompilerInterface $compiler = new Compiler()
    ) {
    }

    /**
     * Handle group operations via callback mechanism
     *
     * This method implements the callback interface for RouteGroup communication.
     * It handles all group operations including route addition and configuration updates
     * without requiring direct RouteMap dependency in groups.
     *
     * Supported operations:
     * - 'route_add': Add a new route from group to collection
     * - 'group_change': Handle group configuration changes (middleware, tokens)
     *
     * Operation data structures:
     * - route_add: ['route' => RouteRecord, 'originalRoute' => RouteRecord]
     * - group_change: ['group' => RouteGroup, 'operation' => string, 'routeNames' => array, 'data' => array]
     *
     * @param string $operation Type of operation (route_add, group_change)
     * @param string $groupName Name of the group performing the operation
     * @param array $data Operation-specific data
     */
    private function handleGroupOperation(string $operation, string $groupName, array $data): void
    {
        switch ($operation) {
            case 'route_add':
                $route = $data['route'];
                $originalRoute = $data['originalRoute'];

                // Add processed route to collection
                $this->addRoute($route);

                // Store original route data for future updates
                $this->routeGroupData[$route->name] = [
                    'groupName' => $groupName,
                    'originalData' => $originalRoute->toArray()
                ];
                break;

            case 'group_change':
                $group = $data['group'];
                $changeType = $data['operation'];
                $routeNames = $data['routeNames'];

                $this->handleGroupConfigurationChange($group, $changeType, $routeNames);
                break;
        }
    }

    /**
     * Handle group configuration changes by updating affected routes.
     *
     * This method implements intelligent route updating that preserves route positions
     * and minimizes processing overhead when group configurations change. It uses
     * optimal update strategies based on what actually changed in the group.
     *
     * Smart update logic:
     * - Reconstructs routes from original data with new group settings
     * - Uses position-preserving update methods
     * - Maintains original route data for future changes
     *
     * Update process:
     * 1. Get original route data (before group processing)
     * 2. Get newly processed route from group
     * 3. Choose optimal update strategy (update vs replace)
     * 4. Update route in collection maintaining position
     * 5. Update group data tracking for future changes
     *
     * Supported change types:
     * - middleware_update: Group middleware stack changes
     * - tokens_update: Group token pattern changes
     *
     * @param RouteGroup $group The group that changed configuration
     * @param string $changeType Type of change (middleware_update|tokens_update)
     * @param array<string> $routeNames List of route names that need updates
     */
    private function handleGroupConfigurationChange(RouteGroup $group, string $changeType, array $routeNames): void
    {
        foreach ($routeNames as $routeName) {
            if (!isset($this->routes[$routeName]) || !isset($this->routeGroupData[$routeName])) {
                continue;
            }

            // Get original route data
            $originalData = $this->routeGroupData[$routeName]['originalData'];
            $originalRoute = RouteRecord::fromArray($originalData);

            // Get newly processed route from group with current settings
            // We need to process the original route with current group settings
            $processedRoutes = $group->getProcessedRoutes();
            $updatedRoute = $processedRoutes[$routeName] ?? null;

            if (!$updatedRoute) {
                continue;
            }

            // Smart strategy selection based on what actually changed
            $nameChanged = $updatedRoute->name !== $routeName;

            if ($nameChanged) {
                // Route name changed → need to change array key → use replacement strategy
                $this->replaceRouteInternal($routeName, $updatedRoute);

                // Update route group data with new name
                unset($this->routeGroupData[$routeName]);
                $this->routeGroupData[$updatedRoute->name] = [
                    'groupName' => $group->name,
                    'originalData' => $originalData
                ];
            } else {
                // Route name unchanged → can update in place → use update strategy
                $this->updateRouteInternal($routeName, $updatedRoute);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): \Generator
    {
        foreach ($this->routes as $route) yield $route;
    }

    /**
     * @inheritDoc
     */
    public function getRoute(string $name): ?RouteRecord
    {
        return $this->routes[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function addRoute(RouteRecord $route): self
    {
        $path = (string) $route->path;
        $name = $route->name;

        if (isset($this->routes[$name])) {
            RouterException::throwForAlreadyRegisteredRoute($name);
        }

        // Categorize route as static or dynamic based on parameters
        if (!$this->compiler->isParametrized($path)) {
            $this->addToMap('static', $path, $name);
        } else {
            $this->addToMap('dynamic', $path, $name);
        }

        $this->routes[$name] = $route;
        $this->indexesDirty = true;

        return $this;
    }

    /**
     * Check if a route exists
     *
     * @param string $name Route name
     * @return bool
     */
    private function hasRoute(string $name): bool
    {
        return isset($this->routes[$name]);
    }

    /**
     * Internal method to replace a route preserving its position
     *
     * Used when route name changes (e.g., group name prefix applied) - we need to change
     * the array key but preserve the route's position in the collection.
     *
     * Strategy: Rebuild the routes array with replacement at the same position.
     *
     * @param string $oldName Current route name (array key to replace)
     * @param RouteRecord $newRoute New route record (with potentially different name)
     * @return self
     */
    private function replaceRouteInternal(string $oldName, RouteRecord $newRoute): self
    {
        if (!isset($this->routes[$oldName])) {
            throw new RouterException('Route "' . $oldName . '" does not exist.');
        }

        $oldRoute = $this->routes[$oldName];
        $oldPath = (string) $oldRoute->path;
        $newPath = (string) $newRoute->path;
        $newName = $newRoute->name;

        // Rebuild routes array preserving order but changing the key
        $newRoutes = [];
        foreach ($this->routes as $key => $route) {
            if ($key === $oldName) {
                $newRoutes[$newName] = $newRoute;  // Replace with new name/route
            } else {
                $newRoutes[$key] = $route;         // Keep existing routes
            }
        }
        $this->routes = $newRoutes;

        // Update map structures
        $this->removeFromMap($oldPath, $oldName);

        if (!$this->compiler->isParametrized($newPath)) {
            $this->addToMap('static', $newPath, $newName);
        } else {
            $this->addToMap('dynamic', $newPath, $newName);
        }

        return $this;
    }

    /**
     * Internal method to update a route in place
     *
     * Used when route name stays the same but other properties change
     * (middleware, path, tokens, etc.). This is the fastest update method
     * since position is preserved automatically.
     *
     * Strategy: Direct replacement in the same array key.
     *
     * @param string $name Route name (must match route->name)
     * @param RouteRecord $route New route record
     * @return self
     */
    private function updateRouteInternal(string $name, RouteRecord $route): self
    {
        if (!isset($this->routes[$name])) {
            throw new RouterException('Route "' . $name . '" does not exist.');
        }

        if ($route->name !== $name) {
            throw new RouterException('Route name mismatch: expected "' . $name . '", got "' . $route->name . '".');
        }

        $oldRoute = $this->routes[$name];
        $oldPath = (string) $oldRoute->path;
        $newPath = (string) $route->path;

        // Remove old route from map
        $this->removeFromMap($oldPath, $name);

        // Add new route to map
        if (!$this->compiler->isParametrized($newPath)) {
            $this->addToMap('static', $newPath, $name);
        } else {
            $this->addToMap('dynamic', $newPath, $name);
        }

        // Update route in place - position preserved automatically
        $this->routes[$name] = $route;

        return $this;
    }

    /**
     * Internal method to remove a route
     *
     * @param string $name Route name
     * @return self
     */
    private function removeRouteInternal(string $name): self
    {
        if (!isset($this->routes[$name])) {
            throw new RouterException('Route "' . $name . '" does not exist.');
        }

        $route = $this->routes[$name];
        $path = (string) $route->path;

        // Remove from map
        $this->removeFromMap($path, $name);

        // Remove from routes
        unset($this->routes[$name]);

        // Clean up group data if exists
        unset($this->routeGroupData[$name]);

        return $this;
    }

    /**
     * Remove route name from the appropriate map category
     *
     * @param string $path Route path
     * @param string $name Route name
     */
    protected function removeFromMap(string $path, string $name): void
    {
        // Check both static and dynamic maps
        foreach (['static', 'dynamic'] as $type) {
            if (isset($this->map[$type][$path])) {
                if (is_array($this->map[$type][$path])) {
                    $key = array_search($name, $this->map[$type][$path], true);
                    if ($key !== false) {
                        unset($this->map[$type][$path][$key]);
                        $this->map[$type][$path] = array_values($this->map[$type][$path]);

                        // If only one route left, convert back to string
                        if (count($this->map[$type][$path]) === 1) {
                            $this->map[$type][$path] = $this->map[$type][$path][0];
                        } elseif (empty($this->map[$type][$path])) {
                            unset($this->map[$type][$path]);
                        }
                    }
                } elseif ($this->map[$type][$path] === $name) {
                    unset($this->map[$type][$path]);
                }
                break;
            }
        }
    }

    /**
     * Add route name to the appropriate map category
     *
     * @param string $type Map type ('static' or 'dynamic')
     * @param string $path Route path
     * @param string $name Route name
     */
    protected function addToMap(string $type, string $path, string $name): void
    {
        if (isset($this->map[$type][$path])) {
            if (is_array($this->map[$type][$path])) {
                $this->map[$type][$path][] = $name;
            } else {
                $this->map[$type][$path] = [$this->map[$type][$path], $name];
            }
        } else {
            $this->map[$type][$path] = $name;
        }
    }

    /**
     * Clone the Routes instance with all its routes and groups
     */
    public function __clone(): void
    {
        $groups = $this->groups;
        $routes = $this->routes;
        $routeGroupData = $this->routeGroupData;

        $this->routes = [];
        $this->groups = [];
        $this->routeGroupData = [];

        foreach ($routes as $k => $route) {
            $this->routes[$k] = clone $route;
        }

        foreach ($groups as $k => $group) {
            // Create new callback for cloned instance
            $updateCallback = function(string $operation, string $groupName, array $data) {
                $this->handleGroupOperation($operation, $groupName, $data);
            };

            $this->groups[$k] = RouteGroup::copy($group, $updateCallback);
        }

        // Restore route group data
        $this->routeGroupData = $routeGroupData;
    }

    /**
     * Create or get a route group
     *
     * Creates a new route group or returns an existing one. Groups enable shared
     * configuration across multiple routes including path prefixes, middleware,
     * and token patterns. The group name is automatically used as a prefix for
     * all route names within the group.
     *
     * @param string $name Group name (also used as route name prefix)
     * @param string|null $pathPrefix Group path prefix (required for creation)
     * @return RouteGroup
     * @throws RouterException When group not found and no path prefix provided
     */
    public function group(string $name, ?string $pathPrefix = null): RouteGroup
    {
        if (!$pathPrefix) {
            if (!isset($this->groups[$name])) {
                throw new RouterException('Group "' . $name . '" not found');
            }
            return $this->groups[$name];
        }

        // Create callback to handle group operations
        $updateCallback = function(string $operation, string $groupName, array $data) {
            $this->handleGroupOperation($operation, $groupName, $data);
        };

        $group = new RouteGroup($name, $pathPrefix, $updateCallback);

        return $this->groups[$name] = $group;
    }

    /**
     * Match a request against the route collection
     *
     * @param RouteMap $routes The route collection to search
     * @param string $uri The request URI
     * @param string $requestMethod The HTTP method
     * @return RouteRecord|null Matched route with parameters or null
     */
    public function match(RouteMap $routes, string $uri, string $requestMethod): ?RouteRecord
    {
        if ($routes instanceof Matcher && $routes !== $this) {
            $route = $routes->match($routes, $uri, $requestMethod);
            if ($route) return $route;
        }

        if (!$routes instanceof Routes) {
            return $this->matchGenericRouteMap($routes, $uri, $requestMethod);
        }

        if ($routes->indexesDirty) {
            $routes->buildIndexes();
        }

        $path = $this->extractPath($uri);
        $method = HttpMethod::normalize($requestMethod);

        if (isset($routes->staticRouteIndex[$method][$path])) {
            $routeName = $routes->staticRouteIndex[$method][$path];
            return $routes->routes[$routeName];
        }

        $matchedGroup = $routes->findGroupByPrefix($path);
        if ($matchedGroup) {
            foreach ($matchedGroup as $route) {
                $result = $this->matchRoute($route, $path, $method);
                if ($result) return $result;
            }
        }

        return $this->matchDynamicRoutes($routes, $path, $method);
    }

    /**
     * Match request against generic RouteMap implementation
     */
    private function matchGenericRouteMap(RouteMap $routes, string $uri, string $requestMethod): ?RouteRecord
    {
        foreach ($routes as $route) {
            if (!$route->methods->has($method)) continue;
            $result = $this->matchRoute($route, $path, $method);
            if ($result !== null) return $result;
        }

        return null;
    }

    private function buildIndexes(): void
    {
        $this->staticRouteIndex = [];
        $this->groupPrefixTree = [];

        // Build static route index by method
        foreach ($this->map['static'] as $path => $routeNames) {
            $routeNames = is_array($routeNames) ? $routeNames : [$routeNames];

            foreach ($routeNames as $routeName) {
                $route = $this->routes[$routeName];
                foreach ($route->methods->toArray() as $method) {
                    $this->staticRouteIndex[$method][$path] = $routeName;
                }
            }
        }

        // Build group prefix tree (sorted by length descending for longest match first)
        $groupPrefixes = [];
        foreach ($this->groups as $group) {
            $groupPrefixes[strlen($group->pathPrefix)][] = $group;
        }
        krsort($groupPrefixes); // Longer prefixes first
        $this->groupPrefixTree = array_merge(...$groupPrefixes);

        $this->indexesDirty = false;
    }

    private function findGroupByPrefix(string $path): ?RouteGroup
    {
        foreach ($this->groupPrefixTree as $group) {
            if (str_starts_with($path, $group->pathPrefix)) {
                return $group;
            }
        }
        return null;
    }

    private function matchDynamicRoutes(Routes $routes, string $path, string $method): ?RouteRecord
    {
        foreach ($routes->map['dynamic'] as $routePath => $routeNames) {
            $routeNames = is_array($routeNames) ? $routeNames : [$routeNames];

            foreach ($routeNames as $routeName) {
                $route = $routes->routes[$routeName];

                // Quick method check before expensive regex matching
                if (!$route->methods->has($method)) {
                    continue;
                }

                $result = $this->matchRoute($route, $path, $method);
                if ($result) return $result;
            }
        }
        return null;
    }

    /**
     * Generate URL from route name and parameters
     *
     * @param RouteMap $routes The route collection
     * @param string $name Route name
     * @param array<string, mixed> $params Route parameters
     * @return string Generated URL
     * @throws GeneratorException When route not found or parameters invalid
     */
    public function generate(RouteMap $routes, string $name, array $params = []): string
    {
        $route = $routes->getRoute($name);

        if (!$route) {
            RouteNotRegisteredException::throwForRoute($name);
        }

        // Use compiler's generate method for URL generation
        try {
            return $this->compiler->generate($route->path->value, $params);
        } catch (\InvalidArgumentException $e) {
            throw new GeneratorException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $routes = [
            'static' => [],
            'dynamic' => []
        ];

        foreach ($this as $route) {
            $routePath = $route->path->value;
            $compiled = $this->compiler->compile($routePath);

            $routeData = [
                'name' => $route->name,
                'path' => $routePath,
                'methods' => $route->methods->toArray(),
                'handler' => $route->handler,
                'middleware' => $route->middleware,
                'regex' => $compiled->regex,
                'parameters' => $compiled->parameters,
                'defaults' => $route->defaults?->toArray() ?? null,
            ];

            if ($this->compiler->isParametrized($routePath)) $routes['dynamic'][] = $routeData;
            else $routes['static'][] = $routeData;
        }

        return $routes;
    }



    /**
     * Match a single route against path and method
     *
     * @param RouteRecord $route The route to match
     * @param string $path The request path
     * @param string $requestMethod The HTTP method
     * @return RouteRecord|null The matched route with parameters or null
     */
    protected function matchRoute(RouteRecord $route, string $path, string $requestMethod): ?RouteRecord
    {
        // Check HTTP method first (fast check)
        if (!$route->methods->has($requestMethod)) {
            return null;
        }

        $compiled = $this->compiler->compile($route->path->value);

        $extractedParams = $compiled->matches($path, $route->defaults?->toArray() ?? []);
        if ($extractedParams === null) return null;

        return empty($extractedParams) ? $route : $route->withParameters($extractedParams);
    }

    /**
     * Create Routes instance from DI container
     *
     * @param ContainerInterface $container The DI container
     * @return static
     */
    public static function createFromContainer(ContainerInterface $container): static
    {
        return new static($container->get(CompilerInterface::class));
    }
}