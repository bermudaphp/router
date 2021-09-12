<?php

namespace Bermuda\Router;

use ArrayIterator;
use Bermuda\Arr;
use Fig\Http\Message\RequestMethodInterface;
use RuntimeException;

final class Routes implements RouteMap
{
    private array $routes = [];

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->routes);
    }

    /**
     * @return Route[]
     */
    public function toArray(): array
    {
        return $this->routes;
    }

    /**
     * @param string|array $prefix
     * @param callable $callback
     * @return $this
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
     * @param Route|array $route
     * @return $this
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
    public function get(string|array $name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['GET']));
    }

    private function merge(string|array $name, $path, $handler, ?array $methods = null): array
    {
        $methods != null ?: $methods = Route::$requestMethods;

        if (is_array($name)) {
            $data = $name;

            if ($path != null) {
                $data['path'] = $path;
            }

            if ($handler != null) {
                $data['handler'] = $handler;
            }

            $data['methods'] = $methods;

            return $data;
        }

        return compact('name', 'path', 'handler', 'methods');
    }

    /**
     * @inheritDoc
     */
    public function post(string|array $name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['POST']));
    }

    /**
     * @inheritDoc
     */
    public function delete(string|array $name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['DELETE']));
    }

    /**
     * @inheritDoc
     */
    public function put(string|array $name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['PUT']));
    }

    /**
     * @inheritDoc
     */
    public function patch(string|array $name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['PATCH']));
    }

    /**
     * @inheritDoc
     */
    public function options(string|array $name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler, ['OPTIONS']));
    }

    /**
     * @inheritDoc
     */
    public function any(string|array $name, ?string $path = null, $handler = null): RouteMap
    {
        return $this->add($this->merge($name, $path, $handler));
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
