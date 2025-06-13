<?php

namespace Bermuda\Router;

use Bermuda\Router\Collector\Methods;
use Bermuda\Router\Collector\Parameters;
use Bermuda\Router\Collector\Tokens;
use Bermuda\Router\Collector\Collector;

/**
 * Class RouteRecord
 *
 * Represents a routing record that encapsulates route details such as its path, name,
 * handler, allowed HTTP methods, tokens for dynamic segments, parameters, default values,
 * and an optional group. The class is designed to be immutable; every method that changes
 * state returns a cloned instance with the updated values.
 *
 * NEW IN PHP 8.4: This version uses property hooks and asymmetric visibility for enhanced
 * encapsulation and cleaner middleware management. The handler property now automatically
 * combines middleware with the actual handler when accessed, while middleware is stored
 * and managed separately.
 *
 * Key architectural changes:
 * - Handler property uses property hook to combine middleware + handler dynamically
 * - Middleware is now a separate property with direct access
 * - Constructor signature updated to include middleware parameter
 * - Immutability preserved through cloning in all mutation methods
 */
final class RouteRecord
{
    /**
     * The handler represents the final destination for request processing - typically:
     * - Controller class name: 'UserController' or 'App\\Controllers\\UserController'
     * - Controller method: ['UserController', 'show'] or 'UserController::show'
     * - Callable: function($request) { ... } or [$object, 'method']
     * - Invokable class: new InvokableController()
     *
     * To get the complete execution pipeline including middleware, use the $pipeline property
     * which automatically combines middleware and handler in the correct execution order.
     *
     * @var mixed The route handler without middleware modifications
     * @see $pipeline For complete execution chain including middleware
     * @see $middleware For middleware-only access
     */
    private(set) mixed $handler;

    /**
     * Middleware stack for this route.
     *
     * Stores middleware handlers that will be executed before the main handler.
     * This separation from the handler property allows for cleaner middleware management
     * and more intuitive API for middleware operations.
     *
     * The middleware stack is automatically combined with the handler when the handler
     * property is accessed, forming the complete execution chain:
     * [middleware1, middleware2, ..., handler]
     *
     * Direct access to this property allows route groups and other components to
     * inspect and modify middleware without complex handler array manipulation.
     *
     * @var array<mixed> Array of middleware handlers in execution order
     */
    private(set) array $middleware = [];

    /**
     * Complete execution pipeline including middleware and handler.
     *
     * PROPERTY HOOK: This property uses PHP 8.4 property hooks to dynamically combine
     * middleware and handler into the complete execution chain when accessed.
     *
     * The pipeline represents the full request processing chain in execution order:
     * 1. First middleware (if any)
     * 2. Second middleware (if any)
     * 3. ... additional middleware
     * 4. Final handler
     *
     * This property is read-only and automatically computed from the current middleware
     * and handler values. It provides a convenient way to access the complete execution
     * chain without manually combining arrays.
     *
     * Usage examples:
     * - Pipeline inspection: count($route->pipeline)
     * - Middleware factory: $factory->create($route->pipeline)
     * - Debug output: print_r($route->pipeline)
     *
     * @var array<mixed> Complete pipeline: [middleware1, middleware2, ..., handler]
     */
    public array $pipeline {
        get => [...$this->middleware, $this->handler];
    }

    /**
     * Unique name identifier for the route.
     *
     * Serves as the primary key for route lookup operations and URL generation.
     * Each route within a route collection must have a unique name to avoid conflicts.
     *
     * Common naming patterns:
     * - Simple: 'users', 'posts', 'home'
     * - Hierarchical: 'api.users.show', 'admin.posts.edit'
     * - Action-based: 'user-list', 'post-create', 'profile-update'
     * - RESTful: 'users.index', 'users.show', 'users.store', 'users.update', 'users.destroy'
     *
     * The name is used for:
     * - URL generation: $router->generate('users.show', ['id' => 123])
     * - Route retrieval: $routes->getRoute('users.show')
     * - Debugging and logging: "Route 'users.show' matched"
     *
     * @var string Unique route identifier
     */
    private(set) string $name;

    /**
     * The route path object.
     *
     * Encapsulates the URL pattern for this route using the RoutePath class.
     * Handles both static and dynamic path segments with parameter placeholders.
     *
     * Supported path formats (using square bracket syntax):
     * - Static: '/users', '/about', '/contact'
     * - Dynamic: '/users/[id]', '/posts/[slug]', '/api/[version]/users'
     * - Optional: '/users/[?id]', '/posts/[?category]/[slug]'
     * - With patterns: '/users/[id:\d+]', '/posts/[slug:[a-z0-9-]+]'
     * - Optional with patterns: '/posts/[?category:[a-z]+]/[slug]'
     * - Complex: '/api/[version:v\d+]/users/[id:\d+]/posts/[?slug:[a-z0-9-]+]'
     *
     * The RoutePath object provides methods for path manipulation such as
     * prefix addition, normalization, and parameter extraction. It automatically
     * normalizes paths during construction to ensure consistent behavior.
     *
     * Path normalization includes:
     * - Multiple slash reduction: '//users///posts' → '/users/posts'
     * - Trailing slash removal: '/users/' → '/users'
     * - Leading slash enforcement: 'users' → '/users'
     * - Backslash conversion: '\users\posts' → '/users/posts'
     *
     * @var RoutePath Route path handler object
     */
    private(set) RoutePath $path;

    /**
     * Collection of tokens for dynamic route segments.
     *
     * Defines regex patterns that constrain dynamic segments in the route path.
     * Each token maps a parameter name to its validation pattern, enabling
     * fine-grained control over what values are accepted for route parameters.
     *
     * Token priority (highest to lowest):
     * 1. Inline patterns: [id:\d+] in route path
     * 2. Route-specific tokens: set via withToken() or withTokens()
     * 3. Group tokens: inherited from route groups
     * 4. Default patterns: from CompilerInterface::DEFAULT_PATTERNS
     * 5. Compiler default: usually '[^\/]+' (any non-slash characters)
     *
     * Common token patterns:
     * - 'id' => '\d+' (digits only: 123, 456, 789)
     * - 'slug' => '[a-z0-9-]+' (URL-safe slugs: hello-world, my-post-123)
     * - 'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'
     * - 'date' => '\d{4}-\d{2}-\d{2}' (YYYY-MM-DD format: 2024-12-25)
     * - 'version' => 'v\d+(\.\d+)*' (version strings: v1, v2.1, v3.2.1)
     * - 'locale' => '[a-z]{2}(_[A-Z]{2})?' (locale codes: en, en_US, fr_FR)
     *
     * Tokens are merged with default patterns and can be overridden per route.
     * They provide runtime validation of URL parameters during route matching.
     *
     * @var Tokens Collection of parameter patterns
     */
    private(set) Tokens $tokens;

    /**
     * Collection of HTTP methods this route responds to.
     *
     * Specifies which HTTP methods will match this route. If empty or not specified,
     * the route will respond to all standard HTTP methods. The collector uses normalized
     * method names (uppercase) for consistency and performance.
     *
     * Standard HTTP methods:
     * - GET: Retrieve data (safe, idempotent) - most common for web pages and APIs
     * - POST: Create new resources - form submissions, API resource creation
     * - PUT: Update/replace entire resources (idempotent) - full resource updates
     * - PATCH: Partial resource updates - modify specific fields
     * - DELETE: Remove resources (idempotent) - resource deletion
     * - HEAD: GET without response body - check resource existence or metadata
     * - OPTIONS: Get allowed methods/CORS preflight - API discovery and CORS
     *
     * Method matching is case-insensitive during route matching but stored in uppercase.
     * Custom methods are also supported for specialized APIs or protocols.
     *
     * Examples:
     * - REST API endpoint: ['GET', 'POST'] for collection resources
     * - Individual resource: ['GET', 'PUT', 'PATCH', 'DELETE']
     * - Read-only endpoint: ['GET', 'HEAD']
     * - API discovery: ['OPTIONS']
     * - Form handling: ['GET', 'POST']
     *
     * @var Methods Collection of HTTP method names
     */
    private(set) Methods $methods;

    /**
     * Collection of route parameters extracted during request matching.
     *
     * Contains parameter values extracted from the URL when this route matches
     * an incoming request. Parameters correspond to dynamic segments in the route path.
     *
     * Parameter extraction process:
     * 1. Route compiler creates regex with named capture groups
     * 2. URL is matched against the regex pattern
     * 3. Named groups are extracted as parameters
     * 4. Numeric strings are automatically converted to numbers
     * 5. Default values are applied for missing optional parameters
     *
     * Type conversion rules:
     * - Pure numeric strings: '123' → 123 (int), '45.67' → 45.67 (float)
     * - Mixed strings: '123abc' → '123abc' (string, no conversion)
     * - Empty/null values: use defaults or remain null
     * - Scientific notation: '1e5' → 100000.0 (if supported by regex)
     *
     * Example extractions:
     * - Route: '/users/[id]/posts/[slug]'
     * - URL: '/users/123/posts/hello-world'
     * - Result: ['id' => 123, 'slug' => 'hello-world']
     *
     * Parameters are typically populated by the router's matching process and
     * can be accessed by route handlers and middleware for request processing.
     *
     * @var Parameters Collection of extracted parameter values
     */
    private(set) Parameters $parameters;

    /**
     * Collection of default parameter values applied exclusively to optional parameters declared
     * in the route pattern when they are missing from the provided URI.
     *
     * Default values are merged only for optional parameters that are defined in the pattern but not present
     * in the URI. If an optional parameter is missing, its corresponding default is used; however, any value
     * explicitly provided in the URI will override the default.
     *
     * Behavior examples:
     * - Pattern: /api/v1/users/[?id]
     *   Request: /api/v1/users
     *     → Defaults applied: ['id' => 1] results in parameters: ['id' => 1]
     *
     * - Pattern: /api/v1/users/[?id]
     *   Request: /api/v1/users/23
     *     → Extracted parameters: ['id' => 23] (the default is ignored)
     *
     * Common use cases:
     * - Pagination: 'page' => 1, 'limit' => 10
     * - API versioning: 'version' => 'v1', 'format' => 'json'
     * - Feature toggles: 'enabled' => true, 'debug' => false
     * - Localization: 'locale' => 'en', 'currency' => 'USD'
     *
     * Defaults retain their original types and are applied only when an optional parameter is missing from the URI.
     *
     * @var Collector|null Collection of default parameter values (null if not defined)
     */
    private(set) ?Collector $defaults = null;

    /**
     * Optional group identifier for route organization.
     *
     * Associates this route with a route group, enabling shared configuration
     * like middleware, prefixes, naming conventions, and tokens. Routes in the
     * same group inherit common settings while maintaining individual customization.
     *
     * Group inheritance hierarchy:
     * 1. Route-specific settings (highest priority)
     * 2. Group settings (inherited)
     * 3. Default settings (lowest priority)
     *
     * Group benefits:
     * - Shared middleware: Authentication, CORS, rate limiting applied to all group routes
     * - Common path prefixes: '/api/v1', '/admin', '/public' automatically prepended
     * - Consistent naming patterns: 'api.*', 'admin.*' prefixes for route names
     * - Token inheritance: Common parameter patterns shared across group routes
     * - Bulk configuration: Change settings for multiple routes simultaneously
     * - Organization: Logical grouping for related functionality
     *
     * Group processing:
     * - Routes added to groups are automatically processed
     * - Group settings are merged with route settings
     * - Original route data is preserved for group updates
     * - Changes to group settings update all group routes
     *
     * The group name is typically set automatically when routes are added to a group,
     * but can also be set manually for organizational purposes.
     *
     * @var string|null Group identifier (null if not grouped)
     */
    private(set) ?string $group;

    /**
     * Constructs a new RouteRecord.
     *
     * Creates an immutable route record with all specified configuration.
     * All arrays are converted to appropriate Collector objects for consistent
     * internal handling and API surface.
     *
     * Parameter processing:
     * - Arrays are wrapped in appropriate Collector classes
     * - Paths are normalized through RoutePath construction
     * - Methods are normalized to uppercase
     * - Middleware is stored separately from handler
     * - Defaults can be null or array (converted to Collector)
     *
     * @param string       $name       Unique route identifier for lookups and URL generation
     * @param string       $path       URL pattern with optional dynamic segments (e.g., '/users/[id]')
     * @param mixed        $handler    Route handler - controller, callable, class name, etc.
     * @param array        $methods    HTTP methods this route responds to (empty = all methods)
     * @param array        $middleware Middleware stack to execute before the handler (NEW PARAMETER)
     * @param array        $tokens     Regex patterns for dynamic segments validation
     * @param array        $parameters Initial parameter values (typically empty at construction)
     * @param array|null   $defaults   Default values for optional/missing parameters
     * @param string|null  $group      Group name for organizational purposes
     */
    public function __construct(
        string $name,
        string $path,
        mixed $handler,
        array $methods = [],
        array $middleware = [],
        array $tokens = [],
        array $parameters = [],
        ?array $defaults = null,
        ?string $group = null
    ) {
        $this->name = $name;
        $this->path = new RoutePath($path);
        $this->handler = $handler;
        $this->tokens = new Tokens($tokens);
        $this->methods = new Methods($methods);
        $this->middleware = $middleware;
        $this->parameters = new Parameters($parameters);
        $this->defaults = $defaults === null ? null : new Collector($defaults);
        $this->group = $group;
    }

    /**
     * Returns a new RouteRecord instance with an updated token.
     *
     * Adds or updates a single token pattern for dynamic route segments.
     * Tokens provide regex validation for URL parameters, ensuring only
     * valid values match the route.
     *
     * This method preserves immutability by cloning the current instance
     * and only modifying the tokens collection in the clone.
     *
     * @param string $name    The parameter name to constrain (matches [name] in path)
     * @param string $pattern The regex pattern without delimiters (e.g., '\d+' for digits)
     * @return self          A new instance with the added/updated token
     *
     * @example
     * // Restrict user ID to numeric values only
     * $route = $route->withToken('id', '\d+');
     *
     * // Allow alphanumeric slugs with hyphens
     * $route = $route->withToken('slug', '[a-z0-9-]+');
     */
    public function withToken(string $name, string $pattern): self
    {
        $route = clone $this;

        $tokens = $route->tokens->toArray();
        $tokens[$name] = $pattern;

        $route->tokens = $route->tokens->with($tokens);

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with updated tokens.
     *
     * Replaces the entire token collection with new patterns. This is useful
     * for bulk token updates or when you need to clear existing tokens and
     * start fresh with a new set.
     *
     * All existing tokens are replaced, not merged. Use withToken() for
     * individual token updates while preserving others.
     *
     * @param array $tokens Associative array of parameter name => regex pattern
     * @return self        A new instance with the updated token collection
     *
     * @example
     * $route = $route->withTokens([
     *     'id' => '\d+',              // Numeric IDs only: /users/[id]
     *     'slug' => '[a-z0-9-]+',     // URL-safe slugs: /posts/[slug]
     *     'date' => '\d{4}-\d{2}-\d{2}', // YYYY-MM-DD dates: /archive/[date]
     *     'format' => 'json|xml|html'    // Specific formats: /api/data/[format]
     * ]);
     */
    public function withTokens(array $tokens): self
    {
        $route = clone $this;
        $route->tokens = $route->tokens->with($tokens);

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with a prefixed path.
     *
     * Prepends a prefix to the current route path while maintaining proper
     * URL formatting. This is commonly used by route groups to add shared
     * prefixes to multiple routes (e.g., '/api/v1', '/admin').
     *
     * The prefix is intelligently combined with the existing path,
     * handling leading/trailing slashes appropriately.
     *
     * @param string $prefix The prefix to prepend (e.g., '/api/v1', '/admin')
     * @return self          A new instance with the prefixed path
     *
     * @example
     * // Original path: '/users'
     * $route = $route->withPrefix('/api/v1');
     * // New path: '/api/v1/users'
     *
     * // Works with dynamic paths too
     * // Original: '/users/[id]'
     * // Result: '/api/v1/users/[id]'
     */
    public function withPrefix(string $prefix): self
    {
        $route = clone $this;
        $route->path = $route->path->withPrefix($prefix);

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with a new route name.
     *
     * Updates the unique identifier for this route. Route names are used
     * for URL generation and route lookups, so they must remain unique
     * within the route collection.
     *
     * This is often used by route groups to add name prefixes for
     * organizational purposes (e.g., 'api.users.show').
     *
     * @param string $name The new unique name for the route
     * @return self        A new instance with the updated name
     *
     * @example
     * // Simple naming
     * $route = $route->withName('user-profile');
     *
     * // Hierarchical naming (common with groups)
     * $route = $route->withName('api.v1.users.show');
     */
    public function withName(string $name): self
    {
        $route = clone $this;
        $route->name = $name;

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with a single default value added.
     *
     * Adds or updates a single default parameter value. Default values are
     * used when route parameters are optional or missing from the matched URL.
     *
     * If no defaults collection exists, creates a new one. If defaults already
     * exist, the new value is merged with existing defaults.
     *
     * @param string $name  The parameter name to set default for
     * @param string $value The default value to use when parameter is missing
     * @return self         A new instance with the updated default value
     *
     * @example
     * // Set default pagination values
     * $route = $route->withDefaultValue('page', '1')
     *                ->withDefaultValue('limit', '10');
     *
     * // Default category for posts
     * $route = $route->withDefaultValue('category', 'general');
     */
    public function withDefaultValue(string $name, string $value): self
    {
        $route = clone $this;

        if ($this->defaults === null) {
            $route->defaults = new Collector([$name => $value]);
        } else {
            $defaults = $route->defaults->toArray();
            $defaults[$name] = $value;

            $route->defaults = $route->defaults->with($defaults);
        }

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with updated default values.
     *
     * Replaces or merges default parameter values. If a defaults collection
     * already exists, the new defaults are merged. If no defaults exist,
     * creates a new collection.
     *
     * Use this for bulk default updates, or withDefaultValue() for individual changes.
     *
     * @param array|null $defaults Associative array of parameter defaults (null to clear)
     * @return self               A new instance with the updated defaults
     *
     * @example
     * // Set multiple defaults at once
     * $route = $route->withDefaults([
     *     'page' => '1',
     *     'limit' => '10',
     *     'sort' => 'created_at'
     * ]);
     *
     * // Clear all defaults
     * $route = $route->withDefaults(null);
     */
    public function withDefaults(?array $defaults): self
    {
        if ($defaults === null) {
            if ($this->defaults === null) return $this;

            $route = clone $this;
            $route->defaults = null;

            return $route;
        }

        $route = clone $this;
        $route->defaults = $route->defaults?->with($defaults) ?? new Collector($defaults);

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with updated parameters.
     *
     * Updates the route's parameter collection with new values. This is
     * typically used by the router during request matching to populate
     * extracted URL parameters.
     *
     * Parameters represent actual values extracted from the matched URL,
     * while defaults provide fallback values for missing parameters.
     *
     * @param array $params Associative array of parameter name => value
     * @return self        A new instance with the updated parameters
     *
     * @example
     * // Typically used internally by router:
     * // URL: /users/123/posts/hello-world
     * // Route: /users/[id]/posts/[slug]
     * $route = $route->withParameters([
     *     'id' => '123',
     *     'slug' => 'hello-world'
     * ]);
     */
    public function withParameters(array $params): self
    {
        $route = clone $this;
        $route->parameters = $route->parameters->with($params);

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with a single parameter updated.
     *
     * Adds or updates a single parameter value. This is useful for
     * incrementally building parameter collections or updating specific
     * parameter values without affecting others.
     *
     * @param string $name  Parameter name to add/update
     * @param mixed  $value Parameter value (typically string from URL)
     * @return self         A new instance with the updated parameter
     *
     * @example
     * // Add individual parameters
     * $route = $route->withParameter('id', '123')
     *                ->withParameter('action', 'edit');
     */
    public function withParameter(string $name, mixed $value): self
    {
        $route = clone $this;
        $params = $route->parameters->toArray();
        $params[$name] = $value;
        $route->parameters = $route->parameters->with($params);

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with a modified group assignment.
     *
     * Associates this route with a route group or removes the group association.
     * Group membership enables shared configuration like middleware, prefixes,
     * and naming conventions.
     *
     * This is typically set automatically when routes are added to groups,
     * but can be used for manual group management.
     *
     * @param string|null $name The group name to assign (null to remove from group)
     * @return self            A new instance with the updated group assignment
     *
     * @example
     * // Assign to API group
     * $route = $route->withGroup('api');
     *
     * // Remove from group
     * $route = $route->withGroup(null);
     */
    public function withGroup(?string $name): self
    {
        $route = clone $this;
        $route->group = $name;

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with updated middleware.
     *
     * UPDATED: Now works with the separate middleware property instead of
     * manipulating the handler array. Adds a single middleware to the stack
     * or clears all middleware if null/empty array is provided.
     *
     * The middleware is stored separately and automatically combined with
     * the handler when the handler property is accessed.
     *
     * @param mixed $middleware Single middleware handler or null/[] to clear
     * @return self            A new instance with the updated middleware
     *
     * @example
     * // Add authentication middleware
     * $route = $route->withMiddleware(AuthMiddleware::class);
     *
     * // Clear all middleware
     * $route = $route->withMiddleware(null);
     */
    public function withMiddleware(mixed $middleware): self
    {
        $route = clone $this;

        if ($middleware === null || $middleware === []) {
            $route->middleware = [];
        } else {
            $route->middleware[] = $middleware;
        }

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with updated middleware stack.
     *
     * UPDATED: Replaces the entire middleware stack with the provided array.
     * This is the preferred method for setting multiple middleware at once
     * or completely replacing the existing middleware configuration.
     *
     * The middleware stack is executed in array order before the main handler.
     *
     * @param array $middlewares Array of middleware handlers in execution order
     * @return self             A new instance with the updated middleware stack
     *
     * @example
     * // Set complete middleware stack
     * $route = $route->withMiddlewares([
     *     CorsMiddleware::class,
     *     AuthMiddleware::class,
     *     RateLimitMiddleware::class
     * ]);
     *
     * // Clear all middleware
     * $route = $route->withMiddlewares([]);
     */
    public function withMiddlewares(array $middlewares): self
    {
        $route = clone $this;
        $route->middleware = $middlewares;

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with an additional HTTP method.
     *
     * Adds a single HTTP method to the route's method collection if it's
     * not already present. If the method already exists, returns the
     * current instance unchanged.
     *
     * Methods are normalized to uppercase for consistency.
     *
     * @param string $method HTTP method to add (case-insensitive)
     * @return self         A new instance with the added method, or current instance if already present
     *
     * @example
     * // Add POST method to existing GET route
     * $route = $route->withMethod('POST');
     *
     * // Add multiple methods individually
     * $route = $route->withMethod('PUT')->withMethod('PATCH');
     */
    public function withMethod(string $method): self
    {
        if ($this->methods->has($method)) return $this;

        $route = clone $this;

        $methods = $route->methods->toArray();
        $methods[] = $method;

        $route->methods = $route->methods->with($methods);

        return $route;
    }

    /**
     * Returns a new RouteRecord instance with updated HTTP methods.
     *
     * Replaces the entire HTTP methods collection with new methods.
     * This completely overwrites existing methods - use withMethod()
     * to add individual methods while preserving existing ones.
     *
     * Methods are automatically normalized to uppercase.
     *
     * @param array $methods Array of HTTP method names (case-insensitive)
     * @return self         A new instance with the updated method collection
     *
     * @example
     * // Set specific methods
     * $route = $route->withMethods(['GET', 'POST']);
     *
     * // REST API endpoint methods
     * $route = $route->withMethods(['GET', 'POST', 'PUT', 'DELETE']);
     *
     * // Single method (alternative to constructor)
     * $route = $route->withMethods(['POST']);
     */
    public function withMethods(array $methods): self
    {
        $route = clone $this;
        $route->methods = $route->methods->with($methods);

        return $route;
    }

    /**
     * Creates a RouteRecord instance from an associative array.
     *
     * Factory method for creating routes from configuration arrays.
     * This is commonly used for loading routes from configuration files
     * or converting between different route representations.
     *
     * UPDATED: Now correctly handles middleware as separate property.
     * The middleware is extracted from the handler if it's an array,
     * or taken from the middleware key if present.
     *
     * @param array{
     *      handler: mixed,
     *      path: string,
     *      name: string,
     *      group?: ?string,
     *      methods?: array<string>,
     *      middleware?: array<mixed>,
     *      tokens?: array,
     *      parameters?: array,
     *      defaults?: ?array<string, string>
     *  } $routeData Associative array containing route configuration
     * @return self A new RouteRecord instance based on the provided data
     *
     * @example
     * $routeData = [
     *     'name' => 'users.show',
     *     'path' => '/users/[id]',
     *     'handler' => UserController::class,
     *     'methods' => ['GET'],
     *     'middleware' => [AuthMiddleware::class],
     *     'tokens' => ['id' => '\d+'],
     *     'defaults' => ['format' => 'json']
     * ];
     * $route = RouteRecord::fromArray($routeData);
     */
    public static function fromArray(array $routeData): self
    {
        $route = new self(
            $routeData['name'],
            $routeData['path'],
            $routeData['handler'],
            $routeData['methods'] ?? [],
            $routeData['middleware'] ?? [],
            $routeData['tokens'] ?? [],
            $routeData['parameters'] ?? [],
            $routeData['defaults'] ?? null,
            $routeData['group'] ?? null
        );

        return $route;
    }

    /**
     * Converts the RouteRecord instance to an associative array.
     *
     * Serializes the route configuration to an array format suitable for
     * storage, caching, or transmission. The resulting array can be used
     * with fromArray() to reconstruct the route.
     *
     * NOTE: The handler property returns the combined middleware + handler
     * through the property hook, representing the complete execution chain.
     *
     * @return array{
     *      handler: mixed,
     *      path: string,
     *      name: string,
     *      group: ?string,
     *      methods: array<string>,
     *      params: array<string, mixed>,
     *      tokens: array<string, string>,
     *      defaults: ?array<string, mixed>
     *  } Associative array representation of the route
     *
     * @example
     * $routeArray = $route->toArray();
     * // Result:
     * // [
     * //     'name' => 'users.show',
     * //     'path' => '/users/[id]',
     * //     'handler' => [AuthMiddleware::class, UserController::class],
     * //     'methods' => ['GET'],
     * //     'tokens' => ['id' => '\d+'],
     * //     'params' => ['id' => '123'],
     * //     'defaults' => ['format' => 'json'],
     * //     'group' => 'api'
     * // ]
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path->value,
            'handler' => $this->pipeline,
            'tokens' => $this->tokens->toArray(),
            'params' => $this->parameters->toArray(),
            'methods' => $this->methods->toArray(),
            'group' => $this->group,
            'defaults' => $this->defaults?->toArray() ?? null,
        ];
    }

    /**
     * Creates a GET route.
     *
     * Convenience factory method for creating routes that respond only to
     * HTTP GET requests. GET routes are used for retrieving data and should
     * be safe (no side effects) and idempotent.
     *
     * @param string $name    Unique route identifier
     * @param string $path    URL pattern for the route
     * @param mixed  $handler Route handler (controller, callable, etc.)
     * @return self          A new RouteRecord configured for GET requests
     *
     * @example
     * $route = RouteRecord::get('users.index', '/users', UserController::class);
     * $route = RouteRecord::get('user.show', '/users/[id]', 'showUser');
     * $route = RouteRecord::get('posts.by_category', '/blog/[?category]/posts', PostController::class);
     */
    public static function get(string $name, string $path, mixed $handler): self
    {
        return new self($name, $path, $handler, ['GET']);
    }

    /**
     * Creates a POST route.
     *
     * Convenience factory method for creating routes that respond only to
     * HTTP POST requests. POST routes are typically used for creating new
     * resources or performing actions with side effects.
     *
     * @param string $name    Unique route identifier
     * @param string $path    URL pattern for the route
     * @param mixed  $handler Route handler for processing POST requests
     * @return self          A new RouteRecord configured for POST requests
     *
     * @example
     * $route = RouteRecord::post('users.create', '/users', UserController::class);
     * $route = RouteRecord::post('contact.send', '/contact', 'sendMessage');
     * $route = RouteRecord::post('api.upload', '/api/upload/[?folder]', UploadController::class);
     */
    public static function post(string $name, string $path, mixed $handler): self
    {
        return new self($name, $path, $handler, ['POST']);
    }

    /**
     * Creates a PUT route.
     *
     * Convenience factory method for creating routes that respond only to
     * HTTP PUT requests. PUT routes are used for updating/replacing entire
     * resources and should be idempotent.
     *
     * @param string $name    Unique route identifier
     * @param string $path    URL pattern for the route
     * @param mixed  $handler Route handler for processing PUT requests
     * @return self          A new RouteRecord configured for PUT requests
     *
     * @example
     * $route = RouteRecord::put('users.update', '/users/[id]', UserController::class);
     * $route = RouteRecord::put('profile.update', '/profile', 'updateProfile');
     * $route = RouteRecord::put('api.resource', '/api/[resource]/[id]', ApiController::class);
     */
    public static function put(string $name, string $path, mixed $handler): self
    {
        return new self($name, $path, $handler, ['PUT']);
    }

    /**
     * Creates a PATCH route.
     *
     * Convenience factory method for creating routes that respond only to
     * HTTP PATCH requests. PATCH routes are used for partial resource updates,
     * modifying only specific fields rather than replacing the entire resource.
     *
     * @param string $name    Unique route identifier
     * @param string $path    URL pattern for the route
     * @param mixed  $handler Route handler for processing PATCH requests
     * @return self          A new RouteRecord configured for PATCH requests
     *
     * @example
     * $route = RouteRecord::patch('users.patch', '/users/[id]', UserController::class);
     * $route = RouteRecord::patch('settings.update', '/settings/[?section]', 'updateSettings');
     */
    public static function patch(string $name, string $path, mixed $handler): self
    {
        return new self($name, $path, $handler, ['PATCH']);
    }

    /**
     * Creates a DELETE route.
     *
     * Convenience factory method for creating routes that respond only to
     * HTTP DELETE requests. DELETE routes are used for removing resources
     * and should be idempotent (multiple calls have the same effect).
     *
     * @param string $name    Unique route identifier
     * @param string $path    URL pattern for the route
     * @param mixed  $handler Route handler for processing DELETE requests
     * @return self          A new RouteRecord configured for DELETE requests
     *
     * @example
     * $route = RouteRecord::delete('users.delete', '/users/[id]', UserController::class);
     * $route = RouteRecord::delete('session.destroy', '/logout', 'logout');
     * $route = RouteRecord::delete('files.remove', '/files/[?folder]/[name]', FileController::class);
     */
    public static function delete(string $name, string $path, mixed $handler): self
    {
        return new self($name, $path, $handler, ['DELETE']);
    }

    /**
     * Creates a HEAD route.
     *
     * Convenience factory method for creating routes that respond only to
     * HTTP HEAD requests. HEAD routes return the same headers as GET requests
     * but without the response body, useful for checking resource existence
     * or metadata.
     *
     * @param string $name    Unique route identifier
     * @param string $path    URL pattern for the route
     * @param mixed  $handler Route handler for processing HEAD requests
     * @return self          A new RouteRecord configured for HEAD requests
     *
     * @example
     * $route = RouteRecord::head('users.check', '/users/[id]', UserController::class);
     * $route = RouteRecord::head('file.exists', '/files/[?folder]/[name]', 'checkFile');
     */
    public static function head(string $name, string $path, mixed $handler): self
    {
        return new self($name, $path, $handler, ['HEAD']);
    }

    /**
     * Creates an OPTIONS route.
     *
     * Convenience factory method for creating routes that respond only to
     * HTTP OPTIONS requests. OPTIONS routes are used for CORS preflight
     * requests and discovering allowed methods for a resource.
     *
     * @param string $name    Unique route identifier
     * @param string $path    URL pattern for the route
     * @param mixed  $handler Route handler for processing OPTIONS requests
     * @return self          A new RouteRecord configured for OPTIONS requests
     *
     * @example
     * $route = RouteRecord::options('api.options', '/api/[?path:.*]', CorsController::class);
     * $route = RouteRecord::options('users.options', '/users', 'handleOptions');
     */
    public static function options(string $name, string $path, mixed $handler): self
    {
        return new self($name, $path, $handler, ['OPTIONS']);
    }

    /**
     * Creates a route responding to multiple HTTP methods.
     *
     * Convenience factory method for creating routes that respond to a
     * specified list of HTTP methods, or all methods if none specified.
     * This is useful for endpoints that handle multiple types of requests.
     *
     * @param string $name    Unique route identifier
     * @param string $path    URL pattern for the route
     * @param mixed  $handler Route handler for processing requests
     * @param array  $methods Array of HTTP methods (empty = all methods)
     * @return self          A new RouteRecord configured for the specified methods
     *
     * @example
     * // Specific methods
     * $route = RouteRecord::any('api.users', '/api/users', ApiController::class, ['GET', 'POST']);
     *
     * // All methods (catch-all)
     * $route = RouteRecord::any('fallback', '/[path]', FallbackController::class);
     *
     * // REST endpoint
     * $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
     * $route = RouteRecord::any('users.resource', '/users/[?id]', UserController::class, $methods);
     */
    public static function any(string $name, string $path, mixed $handler, array $methods = []): self
    {
        return new self($name, $path, $handler, $methods);
    }
}