<?php

namespace Bermuda\Router;

use Psr\Container\ContainerInterface;

final class RouterFactory
{
    public function __invoke(ContainerInterface $container): Router
    {
        return new Router($this->matcher($container),
            $this->generator($container), $this->routeMap($container)
        );
    }

    private function matcher(ContainerInterface $container): Matcher
    {
        if ($container->has(Matcher::class)) {
            return $container->get(Matcher::class);
        }

        return new RouteMatcher();
    }

    private function generator(ContainerInterface $container): Matcher
    {
        if ($container->has(Generator::class)) {
            return $container->get(Generator::class);
        }

        return new PathGenerator();
    }

    private function routeMap(ContainerInterface $container): Matcher
    {
        if ($container->has(RouteMap::class)) {
            return $container->get(RouteMap::class);
        }

        return new Routes();
    }
}

