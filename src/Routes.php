<?php

namespace Bermuda\Router;

use Bermuda\Arr;
use Generator;
use RuntimeException;

final class Routes implements RouteMap
{
    private array $routes = [];

    /**
     * @return Route[]
     */
    public function getIterator(): Generator
    {
        foreach ($this->routes as $route) {
            yield $route;
        }
    }

    /**
     * @return Route[]
     */
    public function toArray(): array
    {
        return $this->routes;
    }

    /**
     * @inheritDoc
     */
    public function group(string|array $prefix, callable $callback): RouteMap
    {
        $callback($map = new self);

        if (is_array($prefix)) {
            $mutators = $prefix;
            $prefix = Arr::pull($mutators, 'prefix');

            foreach ($map->routes as $route) {
                foreach ($mutators as $mutator => $v) {
                    $route = $route->{$mutator}($v);
                }

                $this->add($route->withPrefix($prefix));
            }

            return $this;
        }

        foreach ($map->routes as $route) {
            $this->add($route->withPrefix($prefix));
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function add(Route|array $route): RouteMap
    {
        if (is_array($route)) {
            $route = Route::fromArray($route);
        }

        $this->routes[$route->getName()] = $route;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(
        string|array $name, 
        ?string $path = null, 
        $handler = null, 
        string|array $methods = null, 
        ?array $tokens = null, 
        ?array $middleware = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, 'GET', $tokens, $middleware));
    }

    private function merge(string|array $name, $path, $handler, 
                           array|string $methods = null, ?array $tokens = null,
                           ?array $middleware = null): array
    {
        
        if (is_array($name)) {
            $data = array_merge($name, 
                compact('path', 'methods', 'tokens', 'middleware')
            );
        } else {
            $data = compact('name', 'path', 'handler', 'methods', 'tokens', 'middleware');
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function post(
        string|array $name,
        ?string $path = null,
                     $handler = null,
        string|array $methods = null,
        ?array $tokens = null,
        ?array $middleware = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, 'POST', $tokens, $middleware));
    }

    /**
     * @inheritDoc
     */
    public function delete(
        string|array $name,
        ?string $path = null,
                     $handler = null,
        string|array $methods = null,
        ?array $tokens = null,
        ?array $middleware = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, 'DELETE', $tokens, $middleware));
    }

    /**
     * @inheritDoc
     */
    public function put(
        string|array $name,
        ?string $path = null,
                     $handler = null,
        string|array $methods = null,
        ?array $tokens = null,
        ?array $middleware = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, 'PUT', $tokens, $middleware));
    }

    /**
     * @inheritDoc
     */
    public function patch(
        string|array $name,
        ?string $path = null,
                     $handler = null,
        string|array $methods = null,
        ?array $tokens = null,
        ?array $middleware = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, 'PATCH', $tokens, $middleware));
    }

    /**
     * @inheritDoc
     */
    public function options(
        string|array $name,
        ?string $path = null,
                     $handler = null,
        string|array $methods = null,
        ?array $tokens = null,
        ?array $middleware = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, 'OPTIONS', $tokens, $middleware));
    }

    /**
     * @inheritDoc
     */
    public function any(
        string|array $name,
        ?string $path = null,
                     $handler = null,
        string|array $methods = null,
        ?array $tokens = null,
        ?array $middleware = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, $methods, $tokens, $middleware));
    }

    /**
     * @inheritDoc
     */
    public function resource(string $resource): RouteMap
    {
        if (!is_subclass_of($resource, Resource::class)) {
            throw new RuntimeException(sprintf('Resource must be subclass of %s', Resource::class));
        }

        return $resource::register($this);
    }

    /**
     * @inheritDoc
     */
    public function route(string $name): Route
    {
        $route = $this->routes[$name] ?? null;

        if ($route) {
            return $route;
        }

        throw (new Exception\RouteNotFoundException())->setName($name);
    }
}
