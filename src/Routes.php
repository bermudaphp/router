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
    public function resource($name, ?string $path = null, $resource = null): RouteMap
    {
        $route = $this->merge($name, $path, $resource, []);

        if (!is_subclass_of($route['handler'], Resource::class)) {
            throw new RuntimeException(sprintf('Handler must be subclass of %s', Resource::class));
        }

        $showHandler = !is_string($resource) ? [$route['handler'], 'show'] : $route['handler'] . '@show';
        $createHandler = !is_string($resource) ? [$route['handler'], 'create'] : $route['handler'] . '@create';
        $updateHandler = !is_string($resource) ? [$route['handler'], 'update'] : $route['handler'] . '@update';
        $deleteHandler = !is_string($resource) ? [$route['handler'], 'delete'] : $route['handler'] . '@delete';

        $this->add(array_merge($route, ['name' => $route['name'] . '.create', 'handler' => $createHandler, 'methods' => [RequestMethodInterface::METHOD_POST]]));
        $this->add(array_merge($route, ['name' => $route['name'] . '.show', 'handler' => $showHandler, 'path' => $route['path'] . '/?{id}', 'methods' => [RequestMethodInterface::METHOD_GET]]));
        $this->add(array_merge($route, ['name' => $route['name'] . '.delete', 'handler' => $deleteHandler, 'path' => $route['path'] . '/{id}', 'methods' => [RequestMethodInterface::METHOD_DELETE]]));
        $this->add(array_merge($route, ['name' => $route['name'] . '.update', 'handler' => $updateHandler, 'path' => $route['path'] . '/{id}', 'methods' => [RequestMethodInterface::METHOD_PUT]]));

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRoute(string $name): Route
    {
        $route = $this->routes[$name] ?? null;

        if ($route) {
            return $route;
        }

        throw (new Exception\RouteNotFoundException())->setName($name);
    }
}
