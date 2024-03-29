<?php

namespace Bermuda\Router;

use Psr\Http\Message\{
    ServerRequestInterface, ResponseInterface
};

abstract class Resource
{
    protected static bool $registerOptions = true;
    public static function register(RouteMap $routes): RouteMap
    {
        $routes = static::registerGetHandler($routes);
        $routes = static::registerCreateHandler($routes);
        $routes = static::registerUpdateHandler($routes);

        if (static::$registerOptions) {
            $routes = static::registerOptionsHandler($routes);
        }

        return static::registerDestroyHandler($routes);
    }

    abstract public function get(ServerRequestInterface $request): ResponseInterface ;
    abstract public function create(ServerRequestInterface $request): ResponseInterface ;
    abstract public function update(ServerRequestInterface $request): ResponseInterface ;
    abstract public function delete(ServerRequestInterface $request): ResponseInterface ;
    abstract public function options(ServerRequestInterface $request): ResponseInterface ;

    public static function registerOptionsHandler(RouteMap $routes): RouteMap
    {
        return $routes->options(static::getName().'.options', static::getPathPrefix() . '/?'. Attribute::wrap('any'), static::class . '@options');
    }

    public static function getName(): string
    {
        throw new \RuntimeException('Overwrite '. __METHOD__);
    }

    public static function getPathPrefix(): string
    {
        throw new \RuntimeException('Overwrite '. __METHOD__);
    }

    /**
     * @param RouteMap $routes
     * @return RouteMap
     */
    public static function registerGetHandler(RouteMap $routes): RouteMap
    {
        return $routes->get(static::getName().'.get', static::getPathPrefix().'/?'. Attribute::wrap('id'), static::class . '@get');
    }

    /**
     * @param RouteMap $routes
     * @return RouteMap
     */
    public static function registerDestroyHandler(RouteMap $routes): RouteMap
    {
        return $routes->delete(static::getName().'.destroy', static::getPathPrefix().'/'. Attribute::wrap('id'), static::class . '@delete');
    }

    /**
     * @param RouteMap $routes
     * @return RouteMap
     */
    public static function registerCreateHandler(RouteMap $routes): RouteMap
    {
        return $routes->post(static::getName().'.create', static::getPathPrefix(), static::class . '@create');
    }

    /**
     * @param RouteMap $routes
     * @return RouteMap
     */
    public static function registerUpdateHandler(RouteMap $routes): RouteMap
    {
        return $routes->any(static::getName().'.update', static::getPathPrefix().'/?'. Attribute::wrap('id'), static::class . '@update', 'PUT|PATCH');
    }
}
