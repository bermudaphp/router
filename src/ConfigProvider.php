<?php

namespace Bermuda\Router;

use Bermuda\MiddlewareFactory\MiddlewareFactoryInterface;
use Bermuda\Router\Middleware\DispatchRouteMiddleware;
use Bermuda\Router\Middleware\MatchRouteMiddleware;
use Psr\Container\ContainerInterface;

final class ConfigProvider extends \Bermuda\Config\ConfigProvider
{
    /**
     * @inheritDoc
     */
    protected function getFactories(): array
    {
        return [
            Router::class => '\Bermuda\Router\Router::withDefaults',
            MatchRouteMiddleware::class => static function (ContainerInterface $container): MatchRouteMiddleware {
                return new MatchRouteMiddleware(
                    $container->get(MiddlewareFactoryInterface::class),
                    $container->get(Router::class)
                );
            }
        ];
    }
    
    protected function getInvokables(): array
    {
        return [DispatchRouteMiddleware::class];
    }
}
