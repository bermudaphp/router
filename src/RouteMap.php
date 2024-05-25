<?php

namespace Bermuda\Router;

interface RouteMap extends Cacheable, \IteratorAggregate
{
    public function getRoute(string $name): ?RouteRecord;

    public function addRoute(RouteRecord $route): RouteMap;

    public function get(string $name, string $path, mixed $handler): RouteRecord;

    public function post(string $name, string $path, mixed $handler): RouteRecord;

    public function put(string $name, string $path, mixed $handler): RouteRecord;

    public function delete(string $name, string $path, mixed $handler): RouteRecord;

    public function patch(string $name, string $path, mixed $handler): RouteRecord;

    public function head(string $name, string $path, mixed $handler): RouteRecord;

    public function options(string $name, string $path, mixed $handler): RouteRecord;
    
    public function any(string $name, string $path, mixed $handler): RouteRecord;

    /**
     * @throws Exception\RouterException;
     */
    public function group(string $name, ?string $prefix = null): RouteGroup ;
   

    /**
     * @return \Traversable<RouteRecord>
     */
    public function getIterator(): \Traversable;
}
