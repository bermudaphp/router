<?php


namespace Bermuda\Router;


/**
 * Class RouteFactory
 * @package Bermuda\Router
 */
class RouteFactory implements RouteFactoryInterface
{
    /**
     * @param array $routeData
     * @return RouteInterface
     */
    public function make(array $routeData): RouteInterface
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

        $route = new Route($routeData['name'], $routeData['path'], $routeData['handler']);
        
        $route->methods($routeData['methods'] ?? RouteInterface::http_methods);
        $route->tokens($routeData['tokens'] ?? RouteInterface::tokens);
        
        if(isset($routeData['after']))
        {
            $route->after($routeData['after']);
        }
        
        if(isset($routeData['before']))
        {
            $route->before($routeData['before']);
        }
        
        return $route;
    }
}
