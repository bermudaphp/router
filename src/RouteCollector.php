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

    public function any(string $name, string $path, mixed $handler): RouteRecord
    {
        return $this->createRoute($name, $path, $handler)
            ->setMethods(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS']);
    }

    public function addRoute(RouteRecord $route): self
    {
        $this->routes[$route->name] = $route;
        return $this;
    }

    public function get(string $name, string $path, mixed $handler): RouteRecord
    {
        return $this->createRoute($name, $path, $handler)
            ->setMethods(['GET']);
    }

    public function post(string $name, string $path, mixed $handler): RouteRecord
    {
        return $this->createRoute($name, $path, $handler)
            ->setMethods(['POST']);
    }

    public function put(string $name, string $path, mixed $handler): RouteRecord
    {
        return $this->createRoute($name, $path, $handler)
            ->setMethods(['PUT']);
    }

    public function delete(string $name, string $path, mixed $handler): RouteRecord
    {
        return $this->createRoute($name, $path, $handler)
            ->setMethods(['DELETE']);
    }

    public function patch(string $name, string $path, mixed $handler): RouteRecord
    {
        return $this->createRoute($name, $path, $handler)
            ->setMethods(['PATCH']);
    }

    public function head(string $name, string $path, mixed $handler): RouteRecord
    {
        return $this->createRoute($name, $path, $handler)
            ->setMethods(['HEAD']);
    }

    public function options(string $name, string $path, mixed $handler): RouteRecord
    {
        return $this->createRoute($name, $path, $handler)
            ->setMethods(['OPTIONS']);
    }

    private function createRoute(string $name, string $path, mixed $handler): RouteRecord
    {
        $this->addRoute($route = new RouteRecord($name, $path, $handler));
        return $route;
    }
}
