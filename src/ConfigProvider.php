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
        return [Router::class => '\Bermuda\Router\RouterFactory::createRouter'];
    }
}
