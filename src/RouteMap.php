<?php

namespace Bermuda\Router;

use Bermuda\Arrayable;
use IteratorAggregate;
use RuntimeException;

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
    public function route(string $name): Route;

    /**
     * @param string $prefix
     * @param mixed $middleware
     * @param array|null $tokens
     * @param callable|null $callback
     * @throws \InvalidArgumentException if $callback is null
     * @return RouteMap
     */
    public function group(string $prefix, mixed $middleware = null, ?array $tokens = null, callable $callback = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array|null $tokens
     * @param mixed|null $middleware
     * @return RouteMap
     */
    public function get(
        string $name, string $path,
        $handler, ?array $tokens = null,
        mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array|null $tokens
     * @param mixed|null $middleware
     * @return RouteMap
     */
    public function post(
        string $name, string $path,
        $handler, ?array $tokens = null,
        mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array|null $tokens
     * @param mixed|null $middleware
     * @return RouteMap
     */
    public function delete(
        string $name, string $path,
               $handler, ?array $tokens = null,
        mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array|null $tokens
     * @param mixed|null $middleware
     * @return RouteMap
     */
    public function put(
        string $name, string $path,
        $handler, ?array $tokens = null,
        mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array|null $tokens
     * @param mixed|null $middleware
     * @return RouteMap
     */
    public function patch(
        string $name, string $path,
        $handler, ?array $tokens = null,
        mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array|null $tokens
     * @param mixed|null $middleware
     * @return RouteMap
     */
    public function options(
        string $name, string $path,
        $handler, ?array $tokens = null,
        mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array|string|null $methods
     * @param array|null $tokens
     * @param mixed|null $middleware
     * @return RouteMap
     */
    public function any(string $name, string $path,
        $handler, array|string $methods = null, ?array $tokens = null,
        mixed $middleware = null): RouteMap;

    /**
     * @param string $resource must be subclass of Resource
     * @return RouteMap
     * @throws RuntimeException
     */
    public function resource(string $resource): RouteMap;
}
