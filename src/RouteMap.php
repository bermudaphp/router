<?php

namespace Bermuda\Router;

use Bermuda\Arrayable;
use IteratorAggregate;

interface RouteMap extends Arrayable, IteratorAggregate
{
    /**
     * @return Route[]
     */
    public function toArray(): array;

    /**
     * @param string $name
     * @return Route
     * @throws Exception\RouteNotFoundException
     */
    public function getRoute(string $name): Route;

    /**
     * @param Route|array $route
     * @return RouteMap
     */
    public function add(Route|array $route): RouteMap;

    /**
     * @param string|array $prefix
     * @param callable $callback
     * @return RouteMap
     */
    public function group(string|array $prefix, callable $callback): RouteMap;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function get(string|array $name, ?string $path = null, $handler = null): RouteMap;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function post(string|array $name, ?string $path = null, $handler = null): RouteMap;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function delete(string|array $name, ?string $path = null, $handler = null): RouteMap;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function put(string|array $name, ?string $path = null, $handler = null): RouteMap;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function patch(string|array $name, ?string $path = null, $handler = null): RouteMap;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function options(string|array $name, ?string $path = null, $handler = null): RouteMap;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function any(string|array $name, ?string $path = null, $handler = null): RouteMap;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param Resource|string $resource
     * @return RouteMap
     */
    public function resource(string|array $name, ?string $path = null, Resource|string $resource = null): RouteMap;
}
