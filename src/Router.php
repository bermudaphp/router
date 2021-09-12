<?php

namespace Bermuda\Router;

/**
 * @mixin RouteMap
 */
final class Router
{
    public function __construct(private Matcher  $matcher,
        private Generator $generator, private RouteMap $routes
    ){
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return false|mixed
     * @throws \BadMethodCallException
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->routes, $name))
        {
            return call_user_func_array([$this->routes, $name], $arguments);
        }

        throw new \BadMethodCallException(
            sprintf('Method %s does not exist from %s', __METHOD__, self::class)
        );
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

    public static function defaults(): self
    {
        return new self(new RouteMatcher, new PathGenerator, new Routes);
    }

}
