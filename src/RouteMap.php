<?php

namespace Bermuda\Router;

interface RouteMap extends Cacheable, \IteratorAggregate
{
    public function getRoute(string $name): ?RouteRecord;

    public function addRoute(RouteRecord $route): RouteMap;

    /**
     * @throws Exception\RouterException;
     */
    public function group(string $name, ?string $prefix = null): RouteGroup ;

    /**
     * @return \Traversable<RouteRecord>
     */
    public function getIterator(): \Traversable;
}
