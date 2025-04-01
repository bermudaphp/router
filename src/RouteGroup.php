<?php

namespace Bermuda\Router;

final class RouteGroup implements \IteratorAggregate
{
    use RouteCollector;

    private array $tokens = [];
    private array $middleware = [];
    private ?string $namePrefix = null;

    public function __construct(
        public readonly string $name,
        public readonly string $prefix,
        private readonly RouteMap $routeMap,
    ) {
    }

    public function setMiddleware(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function setNamePrefix(string $prefix): self
    {
        $this->namePrefix = $prefix;
        return $this;
    }

    public function setTokens(array $tokens): self
    {
        $this->tokens = $tokens;
        return $this;
    }

    public function addRoute(RouteRecord $route): self
    {
        $routeData = $route->toArray();

        if ($this->middleware !== []) {
            $routeData['handler'] = [...$this->middleware, ...$routeData['handler']];
        }

        if ($this->tokens !== []) {
            $routeData['tokens'] = array_merge($routeData['tokens'], $this->tokens);
        }

        if ($this->namePrefix !== null) {
            $routeData['name'] = $this->namePrefix . $routeData['name'];
        }

        $routeData['path'] = "$this->prefix/{$routeData['path']}";

        $this->routeMap->addRoute(
            $this->routes[$routeData['name']]
                = RouteRecord::fromArray($routeData)
        );

        return $this;
    }

    /**
     * @internal
     */
    public static function copy(RouteMap $newMap, RouteGroup $group): self
    {
        $copy = new self($group->name, $group->prefix, $newMap);
        foreach ($group->routes as $k => $route) {
            $copy->routes[$k] = clone $route;
        }

        return $copy;
    }
}
