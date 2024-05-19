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
        mixed $middleware = null,
    ) {
        if ($middleware) $this->setMiddleware($middleware);
    }

    public function setMiddleware(array $middleware): self
    {
        $this->middleware = $middleware;
        $this->updateRoutes();

        return $this;
    }

    public function setTokens(array $tokens): self
    {
        $this->tokens = $tokens;
        $this->updateRoutes();

        return $this;
    }

    public function addRoute(RouteRecord $route): self
    {
        $this->routes[$route->name] = $route;
        $this->updateRoutes();

        return $this;
    }

    private function createRoute(string $name, string $path, mixed $handler): RouteRecord
    {
        $this->addRoute($route = new RouteRecord($name, "/$this->prefix/$path", $handler));
        return $route;
    }

    private function updateRoutes(): void
    {
        if ($this->middleware === [] && !$this->namePrefix && $this->tokens === []) return;

        foreach ($this->routes as $route) {
            if ($this->middleware !== []) $route->setMiddleware($this->middleware);
            if ($this->tokens !== []) {
                foreach ($this->tokens as $token => $pattern) $route->setToken($token, $pattern);
            }
        }
    }
}
