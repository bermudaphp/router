<?php

namespace Bermuda\Router;

use Bermuda\Arrayable;
use Bermuda\Router\Exception\RouterException;
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
     * @param callable $callback
     * @throws \InvalidArgumentException if $callback is null
     * @return RouteMap
     */
    public function group(string $prefix, mixed $middleware = null, ?array $tokens = null, callable $callback = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param mixed $handler
     * @param mixed|null $middleware
     * @return RouteMap
     * @throws RouterException if $name already exists in map
     */
    public function get(
        string $name, string|Path $path,
        mixed $handler, mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param mixed $handler
     * @param mixed|null $middleware
     * @return RouteMap
     * @throws RouterException if $name already exists in map
     */
    public function post(
        string $name, string|Path $path,
        mixed $handler, mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param mixed $handler
     * @param mixed|null $middleware
     * @return RouteMap
     * @throws RouterException if $name already exists in map
     */
    public function delete(
        string $name, string|Path $path,
        mixed $handler, mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param mixed $handler
     * @param mixed|null $middleware
     * @return RouteMap
     * @throws RouterException if $name already exists in map
     */
    public function put(
        string $name, string|Path $path,
        mixed $handler, mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param mixed $handler
     * @param mixed|null $middleware
     * @return RouteMap
     * @throws RouterException if $name already exists in map
     */
    public function patch(
        string $name, string|Path $path,
        mixed $handler, mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param mixed $handler
     * @param mixed|null $middleware
     * @return RouteMap
     * @throws RouterException if $name already exists in map
     */
    public function options(
        string $name, string|Path $path,
        mixed $handler, mixed $middleware = null): RouteMap;

    /**
     * @param string $name
     * @param string $path
     * @param mixed $handler
     * @param array|string|null $methods
     * @param mixed|null $middleware
     * @return RouteMap
     * @throws RouterException if $name already exists in map
     */
    public function any(string $name, string|Path $path,
        mixed $handler, array|string $methods = null,
        mixed $middleware = null): RouteMap;

    /**
     * Must be subclass of Resource
     * @param string|Resource $resource
     * @return RouteMap
     * @throws RuntimeException
     */
    public function resource(string|Resource $resource): RouteMap;

    /**
     * @param string $filename
     * @param array|null $context
     * @return RouteMap
     */
    public static function createFromCache(string $filename, array $context = null): RouteMap ;

    /**
     * @param string $filename
     * @param callable|null $fileWriter
     * @return void
     */
    public function cache(string $filename, callable $fileWriter = null): void ;
}
