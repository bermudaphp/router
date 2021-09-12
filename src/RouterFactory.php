<?php

namespace Bermuda\Router;

use Psr\Container\ContainerInterface;

final class RouterFactory
{
    public function __invoke(ContainerInterface $container): Router
    {
        return new Router(
            $container->get(Matcher::class),
            $container->get(Generator::class),
            $container->get(RouteMap::class)
        );
    }
}

