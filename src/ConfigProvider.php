<?php

namespace Bermuda\Router;

use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;
use Bermuda\Router\Middleware\DispatchRouteMiddleware;
use Bermuda\Router\Middleware\MatchRouteMiddleware;
use Psr\Container\ContainerInterface;

final class ConfigProvider extends \Bermuda\Config\ConfigProvider
{
    public const string CONFIG_KEY_LIMITERS = 'Bermuda\Router:limiters';

    /**
     * @inheritDoc
     */
    protected function getFactories(): array
    {
        return [
            Router::class => [Router::class, 'createFromContainer'],
            RouteMap::class => [Routes::class, 'createFromContainer'],
            Tokenizer::class => [Tokenizer::class, 'createFromContainer'],
            MatchRouteMiddleware::class => [MatchRouteMiddleware, 'createFromContainer']
        ];
    }

    protected function getInvokables(): array
    {
        return [DispatchRouteMiddleware::class];
    }
}
