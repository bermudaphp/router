<?php

namespace Bermuda\Router;

use Psr\Container\ContainerInterface;
use function Bermuda\Config\cget;

final class RouterFactory
{
    public function __invoke(ContainerInterface $container = null): Router
    {
        return self::createRouter($container);
    }
    
    public static function createRouter(ContainerInterface $container = null): Router
    {
        if ($container === null) {
            return Router::withDefaults();
        }
        
        return new Router(
                cget($container, Matcher::class, static fn() => new RouteMatcher, true),
                cget($container, Generator::class, static fn() => new PathGenerator, true),
                cget($container, RouteMap::class, static fn() => new Routes, true)
        )
    }
}

