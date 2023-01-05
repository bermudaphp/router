<?php

namespace Bermuda\Router;

final class RouteBuilder
{
    private null|array|string $methods = null;
    private ?array $middleware = [], $tokens = null;

    public function __construct(
        private readonly string $name,
        private readonly string $path,
        private readonly mixed $handler
    ) {
    }

    public function middleware(array $middlewareArray): self
    {
        if (!isset($middlewareArray['before']) && !isset($middlewareArray['after'])) {
            $middlewareArray['before'] = $middlewareArray;
        }
        
        $this->middleware['before'] = array_merge($this->middleware['before'], $middlewareArray['before'] ?? []);
        $this->middleware['after'] = array_merge($this->middleware['after'], $middlewareArray['after'] ?? []);

        return $this;
    }

    public function tokens(array $tokens): self
    {
        $this->tokens = $tokens;
        return $this;
    }

    public function methods(array|string $methods): self
    {
        $this->methods = $methods;
        return $this;
    }

    public function prependMiddleware(mixed ... $middleware): self
    {
        return $this->middleware(['before' => $middleware]);
    }

    public function appendMiddleware(mixed ... $middleware): self
    {
        return $this->middleware(['after' => $middleware]);
    }

    public function attach(RouteMap $routes): RouteMap
    {
        return $routes->any($this->name, $this->path, $this->handler, $this->methods, $this->tokens, $this->middleware);
    }
}
