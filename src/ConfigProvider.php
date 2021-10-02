<?php

namespace Bermuda\Router;

use function Bermuda\Config\cget;
use Psr\Container\ContainerInterface;

final class ConfigProvider extends \Bermuda\Config\ConfigProvider
{
    /**
     * @inheritDoc
     */
    protected function getFactories(): array
    {
        return [
            Router::class => static fn(ContainerInterface $container): Router => new Router(
                cget($container, Matcher::class, static fn() => new RouteMatcher, true),
                cget($container, Generator::class, static fn() => new PathGenerator, true),
                cget($container, RouteMap::class, static fn() => new Routes, true)
            )
        ];
    }
}
