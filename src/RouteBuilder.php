<?php

declare(strict_types=1);

namespace Bermuda\Router;

/**
 * Route Builder - Fluent interface for building routes
 *
 * Provides a convenient fluent API for constructing RouteRecord instances with
 * better readability and ease of use compared to direct constructor calls.
 * The builder accumulates configuration through method calls and creates
 * the final RouteRecord when build() is called.
 *
 * Features:
 * - Fluent interface for all route configuration
 * - Method chaining for readable route definitions
 * - Validation of route configuration before building
 * - Support for all RouteRecord features
 * - Convenience methods for common HTTP methods
 * - Reset functionality for builder reuse
 *
 * @example
 * ```php
 * $route = RouteBuilder::create('users.show', '/users/[id]')
 *     ->handler(UserController::class)
 *     ->methods(['GET'])
 *     ->middleware([AuthMiddleware::class, ValidationMiddleware::class])
 *     ->tokens(['id' => '\d+'])
 *     ->defaults(['format' => 'json'])
 *     ->group('api')
 *     ->build();
 * ```
 */
final class RouteBuilder
{
    private string $name = '';
    private string $path = '';
    private mixed $handler = null;
    private array $methods = [];
    private array $middleware = [];
    private array $tokens = [];
    private array $parameters = [];
    private ?array $defaults = null;
    private ?string $group = null;

    /**
     * Private constructor to enforce factory method usage.
     */
    private function __construct()
    {
    }

    /**
     * Create a new RouteBuilder instance.
     *
     * Factory method to create a RouteBuilder with optional initial configuration.
     * The name and path are the minimum required fields for any route.
     *
     * @param string|null $name Optional route name
     * @param string|null $path Optional route path
     * @return self New RouteBuilder instance
     */
    public static function create(?string $name = null, ?string $path = null, mixed $handler = null): self
    {
        $builder = new self();

        if ($name !== null) {
            $builder->name = $name;
        }

        if ($path !== null) {
            $builder->path = $path;
        }

        if ($handler !== null) {
            $builder->handler = $handler;
        }

        return $builder;
    }

    /**
     * Set the route name.
     *
     * @param string $name Unique route identifier
     * @return self Returns this builder for method chaining
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the route path pattern.
     *
     * @param string $path URL pattern with optional parameters in square brackets
     * @return self Returns this builder for method chaining
     */
    public function path(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set the route handler.
     *
     * @param mixed $handler Route handler (controller, callable, class name, etc.)
     * @return self Returns this builder for method chaining
     */
    public function handler(mixed $handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Set HTTP methods for the route.
     *
     * @param array $methods Array of HTTP method names
     * @return self Returns this builder for method chaining
     */
    public function methods(array $methods): self
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * Add a single HTTP method to the route.
     *
     * @param string $method HTTP method name
     * @return self Returns this builder for method chaining
     */
    public function method(string $method): self
    {
        if (!in_array($method, $this->methods, true)) {
            $this->methods[] = $method;
        }
        return $this;
    }

    /**
     * Set the route to accept only GET requests.
     *
     * @return self Returns this builder for method chaining
     */
    public function get(): self
    {
        return $this->methods(['GET']);
    }

    /**
     * Set the route to accept only POST requests.
     *
     * @return self Returns this builder for method chaining
     */
    public function post(): self
    {
        return $this->methods(['POST']);
    }

    /**
     * Set the route to accept only PUT requests.
     *
     * @return self Returns this builder for method chaining
     */
    public function put(): self
    {
        return $this->methods(['PUT']);
    }

    /**
     * Set the route to accept only PATCH requests.
     *
     * @return self Returns this builder for method chaining
     */
    public function patch(): self
    {
        return $this->methods(['PATCH']);
    }

    /**
     * Set the route to accept only DELETE requests.
     *
     * @return self Returns this builder for method chaining
     */
    public function delete(): self
    {
        return $this->methods(['DELETE']);
    }

    /**
     * Set the route to accept only HEAD requests.
     *
     * @return self Returns this builder for method chaining
     */
    public function head(): self
    {
        return $this->methods(['HEAD']);
    }

    /**
     * Set the route to accept only OPTIONS requests.
     *
     * @return self Returns this builder for method chaining
     */
    public function options(): self
    {
        return $this->methods(['OPTIONS']);
    }

    /**
     * Set the route to accept any HTTP methods.
     *
     * @param array $methods Optional specific methods, empty for all methods
     * @return self Returns this builder for method chaining
     */
    public function any(array $methods = []): self
    {
        return $this->methods($methods);
    }

    /**
     * Set middleware stack for the route.
     *
     * @param array $middleware Array of middleware handlers
     * @return self Returns this builder for method chaining
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * Add a single middleware to the route.
     *
     * @param mixed $middleware Middleware handler to add
     * @return self Returns this builder for method chaining
     */
    public function addMiddleware(mixed $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Set parameter tokens/patterns for the route.
     *
     * @param array $tokens Associative array of parameter name => pattern
     * @return self Returns this builder for method chaining
     */
    public function tokens(array $tokens): self
    {
        $this->tokens = $tokens;
        return $this;
    }

    /**
     * Add a single parameter token/pattern.
     *
     * @param string $name Parameter name
     * @param string $pattern Regex pattern for the parameter
     * @return self Returns this builder for method chaining
     */
    public function token(string $name, string $pattern): self
    {
        $this->tokens[$name] = $pattern;
        return $this;
    }

    /**
     * Set route parameters (typically used during matching).
     *
     * @param array $parameters Associative array of parameter values
     * @return self Returns this builder for method chaining
     */
    public function parameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Add a single parameter value.
     *
     * @param string $name Parameter name
     * @param mixed $value Parameter value
     * @return self Returns this builder for method chaining
     */
    public function parameter(string $name, mixed $value): self
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Set default parameter values.
     *
     * @param array|null $defaults Associative array of default values, null to clear
     * @return self Returns this builder for method chaining
     */
    public function defaults(?array $defaults): self
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * Add a single default parameter value.
     *
     * @param string $name Parameter name
     * @param mixed $value Default value
     * @return self Returns this builder for method chaining
     */
    public function defaultValue(string $name, mixed $value): self
    {
        if ($this->defaults === null) {
            $this->defaults = [];
        }
        $this->defaults[$name] = $value;
        return $this;
    }

    /**
     * Set the route group.
     *
     * @param string|null $group Group name, null to remove from group
     * @return self Returns this builder for method chaining
     */
    public function group(?string $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Build and return the RouteRecord instance.
     *
     * Creates a RouteRecord with all the accumulated configuration.
     * Validates that required fields (name, path, handler) are set.
     *
     * @return RouteRecord The constructed route record
     * @throws \InvalidArgumentException If required fields are missing
     */
    public function build(): RouteRecord
    {
        $this->validate();

        return new RouteRecord(
            $this->name,
            $this->path,
            $this->handler,
            $this->methods,
            $this->middleware,
            $this->tokens,
            $this->parameters,
            $this->defaults,
            $this->group
        );
    }

    /**
     * Reset the builder to initial state for reuse.
     *
     * Clears all accumulated configuration, allowing the builder to be reused
     * for creating multiple routes without creating new instances.
     *
     * @return self Returns this builder for method chaining
     */
    public function reset(): self
    {
        $this->name = '';
        $this->path = '';
        $this->handler = null;
        $this->methods = [];
        $this->middleware = [];
        $this->tokens = [];
        $this->parameters = [];
        $this->defaults = null;
        $this->group = null;

        return $this;
    }

    /**
     * Create a copy of this builder with the same configuration.
     *
     * @return self New RouteBuilder instance with copied configuration
     */
    public function copy(): self
    {
        $copy = new self();
        $copy->name = $this->name;
        $copy->path = $this->path;
        $copy->handler = $this->handler;
        $copy->methods = $this->methods;
        $copy->middleware = $this->middleware;
        $copy->tokens = $this->tokens;
        $copy->parameters = $this->parameters;
        $copy->defaults = $this->defaults;
        $copy->group = $this->group;

        return $copy;
    }

    /**
     * Get current builder configuration as array.
     *
     * @return array Current configuration state
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path,
            'handler' => $this->handler,
            'methods' => $this->methods,
            'middleware' => $this->middleware,
            'tokens' => $this->tokens,
            'parameters' => $this->parameters,
            'defaults' => $this->defaults,
            'group' => $this->group,
        ];
    }

    /**
     * Create RouteBuilder from existing RouteRecord.
     *
     * Factory method to create a builder pre-configured with settings from
     * an existing RouteRecord. Useful for modifying existing routes.
     *
     * @param RouteRecord $route Existing route to copy configuration from
     * @return self New RouteBuilder with route configuration
     */
    public static function fromRoute(RouteRecord $route): self
    {
        $builder = new self();
        $builder->name = $route->name;
        $builder->path = (string) $route->path;
        $builder->handler = $route->handler;
        $builder->methods = $route->methods->toArray();
        $builder->middleware = $route->middleware;
        $builder->tokens = $route->tokens->toArray();
        $builder->parameters = $route->parameters->toArray();
        $builder->defaults = $route->defaults?->toArray();
        $builder->group = $route->group;

        return $builder;
    }

    /**
     * Create RouteBuilder from configuration array.
     *
     * Factory method to create a builder from an array configuration,
     * such as those loaded from configuration files or databases.
     *
     * @param array $config Route configuration array
     * @return self New RouteBuilder with array configuration
     */
    public static function fromArray(array $config): self
    {
        $builder = new self();

        if (isset($config['name'])) {
            $builder->name = $config['name'];
        }
        if (isset($config['path'])) {
            $builder->path = $config['path'];
        }
        if (isset($config['handler'])) {
            $builder->handler = $config['handler'];
        }
        if (isset($config['methods'])) {
            $builder->methods = $config['methods'];
        }
        if (isset($config['middleware'])) {
            $builder->middleware = $config['middleware'];
        }
        if (isset($config['tokens'])) {
            $builder->tokens = $config['tokens'];
        }
        if (isset($config['parameters'])) {
            $builder->parameters = $config['parameters'];
        }
        if (isset($config['defaults'])) {
            $builder->defaults = $config['defaults'];
        }
        if (isset($config['group'])) {
            $builder->group = $config['group'];
        }

        return $builder;
    }

    /**
     * Validate the current configuration.
     *
     * Ensures that all required fields are set before building the RouteRecord.
     *
     * @throws \InvalidArgumentException If validation fails
     */
    private function validate(): void
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Route name is required');
        }

        if (empty($this->path)) {
            throw new \InvalidArgumentException('Route path is required');
        }

        if ($this->handler === null) {
            throw new \InvalidArgumentException('Route handler is required');
        }
    }
}