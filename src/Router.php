<?php

namespace Bermuda\Router;

final class Router
{
    public function __construct(
        private Matcher  $matcher,
        private Generator $generator, 
        private RouteMap $routes
    ) {
    }
    
    public function match(string $uri, string $requestMethod):? MatchedRoute
    {
        return $this->matcher->match($this->routes, $uri, $requestMethod);
    }

    /**
     * @throws Exception\GeneratorException
     */
    public function generate(string $name, array $params = []): string
    {
        return $this->generator->generate($this->routes, $name, $params);
    }

    public function withRoutes(RouteMap $routes): self
    {
        $copy = clone $this;
        $copy->routes = $routes;
        
        return $copy;
    }
     
    /**
     * @return RouteMap
     */
    public function getRoutes(): RouteMap
    {
        return clone $this->routes;
    }

    /**
     * @return static
     */
    public static function withDefaults(): self
    {
        return new self($routes = new Routes, $routes, $routes);
    }
}
