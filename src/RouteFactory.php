<?php


namespace Lobster\Routing;


use Fig\Http\Message\RequestMethodInterface;
use Lobster\Resolver\Contracts\Resolver;
use Lobster\Resolver\ResolverInterface;


/**
 * Class RouteFactory
 * @package Lobster\Routing
 */
class RouteFactory implements Contracts\RouteFactory
{
    /**
     * @param array $routeData
     * @return Route
     */
    public function __invoke(array $routeData) : Route
    {

        foreach (['name', 'path', 'handler'] as $key)
        {
            if(!array_key_exists($key, $routeData))
            {
                throw new \InvalidArgumentException(
                    sprintf('Missing %s $routeData[\'%s\']', __METHOD__, $key)
                );
            }
        }

        return new Route($routeData['name'],
            $routeData['path'], $routeData['handler'],
            $routeData['methods'] ?? Route::HTTP_METHODS,
            $routeData['tokens'] ?? Route::ROUTE_TOKENS
        );
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     */
    public function get(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS) : Route
    {
        $methods = [RequestMethodInterface::METHOD_GET];
        return ($this)(compact('name', 'path', 'handler', 'methods', 'tokens'));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     */
    public function post(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS) : Route
    {
        $methods = [RequestMethodInterface::METHOD_POST];
        return ($this)(compact('name', 'path', 'handler', 'methods', 'tokens'));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     */
    public function delete(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS) : Route
    {
        $methods = [RequestMethodInterface::METHOD_DELETE];
        return ($this)(compact('name', 'path', 'handler', 'methods', 'tokens'));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     */
    public function put(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS) : Route
    {
        $methods = [RequestMethodInterface::METHOD_PUT];
        return ($this)(compact('name', 'path', 'handler', 'methods', 'tokens'));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     */
    public function head(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS) : Route
    {
        $methods = [RequestMethodInterface::METHOD_HEAD];
        return ($this)(compact('name', 'path', 'handler', 'methods', 'tokens'));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     */
    public function options(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS) : Route
    {
        $methods = [RequestMethodInterface::METHOD_OPTIONS];
        return ($this)(compact('name', 'path', 'handler', 'methods', 'tokens'));
    }

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     */
    public function any(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS) : Route
    {
        return ($this)(compact('name', 'path', 'handler', 'tokens'));
    }
}
