<?php

namespace Bermuda\Router;

use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;
use Bermuda\Router\Locator\RouteLocator;
use Bermuda\Router\Locator\RouteLocatorInterface;
use Bermuda\Router\Middleware\DispatchRouteMiddleware;
use Bermuda\Router\Middleware\MatchRouteMiddleware;
use Psr\Container\ContainerInterface;

/**
 * Service provider configuration for the router package
 *
 * Registers all necessary services and their factories in the DI container
 */
final class ConfigProvider extends \Bermuda\Config\ConfigProvider
{
    public const string CONFIG_KEY_ROUTES_FILE = 'Bermuda\Router:routes_file';

    /**
     * {@inheritDoc}
     */
    protected function getFactories(): array
    {
        return [

            Router::class => [Router::class, 'createFromContainer'],
            RouteMap::class => [RouteLocatorInterface::class, 'getRoutes'],
            RouteLocatorInterface::class => [RouteLocator::class, 'createFromContainer'],
            MatchRouteMiddleware::class => [MatchRouteMiddleware::class, 'createFromContainer']
        ];
    }

    protected function getAliases(): array
    {
        return [
            CompilerInterface::class => Compiler::class,
            Matcher::class => RouteMap::class,
            Generator::class => RouteMap::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getInvokables(): array
    {
        return [DispatchRouteMiddleware::class, CacheFileProvider::class];
    }
}