<?php

namespace Bermuda\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Resource
{
    public static function register(RouteMap $routes): RouteMap
    {
        $routes = static::registerGetHandler($routes);
        $routes = static::registerCreateHandler($routes);
        $routes = static::registerUpdateHandler($routes);
        $routes = static::registerEditHandler($routes);
        $routes = static::registerDestroyHandler($routes);
        return static::registerStoreHandler($routes);
    }

    abstract public function get(ServerRequestInterface $request): ResponseInterface ;
    abstract public function create(ServerRequestInterface $request): ResponseInterface ;
    abstract public function update(ServerRequestInterface $request): ResponseInterface ;
    abstract public function delete(ServerRequestInterface $request): ResponseInterface ;
    abstract public function edit(ServerRequestInterface $request): ResponseInterface ;
    abstract public function store(ServerRequestInterface $request): ResponseInterface ;

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
        return $routes->get(static::getName().'.get', static::getPathPrefix().'/?{id}', static::class . '@get');
    }

    /**
     * @param RouteMap $routes
     * @return RouteMap
     */
    public static function registerEditHandler(RouteMap $routes): RouteMap
    {
        return $routes->get(static::getName().'.edit', static::getPathPrefix().'/edit/{id}', static::class . '@edit');
    }

    /**
     * @param RouteMap $routes
     * @return RouteMap
     */
    public static function registerDestroyHandler(RouteMap $routes): RouteMap
    {
        return $routes->delete(static::getName().'.destroy', static::getPathPrefix().'/{id}', static::class . '@destroy');
    }

    /**
     * @param RouteMap $routes
     * @return RouteMap
     */
    public static function registerStoreHandler(RouteMap $routes): RouteMap
    {
        return $routes->post(static::getName().'.store', static::getPathPrefix(), static::class . '@store');
    }

    /**
     * @param RouteMap $routes
     * @return RouteMap
     */
    public static function registerCreateHandler(RouteMap $routes): RouteMap
    {
        return $routes->get(static::getName().'.create', static::getPathPrefix().'/create', static::class . '@create');
    }

    /**
     * @param RouteMap $routes
     * @return RouteMap
     */
    public static function registerUpdateHandler(RouteMap $routes): RouteMap
    {
        return $routes->any(['name' => static::getName(), 'methods' => ['PUT', 'PATCH']].'.update', static::getPathPrefix().'/{id}', static::class . '@update');
    }
}
