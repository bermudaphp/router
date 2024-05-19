<?php

namespace Bermuda\Router;

use IteratorAggregate;

interface RouteMap extends IteratorAggregate
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

    /**
     * @return \Traversable<RouteRecord>
     */
    public function getIterator(): \Traversable;
}
