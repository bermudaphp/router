<?php

namespace Bermuda\Router;

trait RouteCollector
{
    /**
     * @var array<RouteRecord>
     */
    protected array $routes = [];

    /**
     * @return \Generator<RouteRecord>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->routes as $route) yield $route;
    }

    public function getRoute(string $name):? RouteRecord
    {
        foreach ($this->routes as $route) {
            if ($route->name === $name) return $route;
        }

        return null;
    }

    public function addRoute(RouteRecord $route): self
    {
        $this->routes[$route->name] = $route;
        return $this;
    }
}
