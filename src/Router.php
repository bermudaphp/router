<?php

namespace Bermuda\Router;

final class Router
{
    public function __construct(
        private Matcher  $matcher, private Generator $generator,
        private RouteMap $routes
    ){
    }

    /**
     * @param string $requestMethod
     * @param string $uri
     * @throws Exception\RouteNotFoundException
     * @throws Exception\MethodNotAllowedException
     */
    public function match(string $requestMethod, string $uri): Route
    {
        return $this->matcher->match($this->routes, $requestMethod, $uri);
    }

    /**
     * @param string $name
     * @param array $attributes
     * @return string
     * @throws Exception\GeneratorException
     * @throws Exception\RouteNotFoundException
     */
    public function generate(string $name, array $attributes = []): string
    {
        return $this->generator->generate($this->routes, $name, $attributes);
    }

    /**
     * @return RouteMap
     */
    public function getRoutes(): RouteMap
    {
        return $this->routes;
    }
}
