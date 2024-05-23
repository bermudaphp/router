<?php

namespace Bermuda\Router;

final class Router
{
    private ?MatchedRoute $currentRoute = null;
    
    public function __construct(
        private Matcher   $matcher,
        private Generator $generator, 
        private RouteMap  $routes
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

    public function setCurrentRoute(MatchedRoute $route): self
    {
        $this->currentRoute = $route;
        return $this;
    }

    public function getCurrentRoute():? MatchedRoute
    {
        return $this->currentRoute;
    }
     
    public function getRoutes(): RouteMap
    {
        return clone $this->routes;
    }

    public static function withDefaults(): self
    {
        return new self($routes = new Routes, $routes, $routes);
    }
    
    public static function fromDnf(Matcher&Generator&RouteMap $routes): self
    {
        return new self($routes, $routes, $routes);
    }
}
