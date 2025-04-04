<?php

namespace Bermuda\Router;

use Psr\Container\ContainerInterface;

final class Router
{
    private ?RouteRecord $currentRoute = null;
    
    public function __construct(
        private Matcher   $matcher,
        private Generator $generator, 
        private RouteMap  $routes
    ) {
    }
    
    public function match(string $uri, string $requestMethod):? RouteRecord
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

    public function setCurrentRoute(RouteRecord $route): self
    {
        $this->currentRoute = $route;
        return $this;
    }

    public function getCurrentRoute():? RouteRecord
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

    public static function createFromContainer(ContainerInterface $container): self
    {
        $routes = $container->get(RouteMap::class);
        try {
            return static::fromDnf($routes);
        } catch (\Throwable $exception) {
            return new self($container->get(Matcher::class), $container->get(Generator::class), $routes);
        }
    }
}
