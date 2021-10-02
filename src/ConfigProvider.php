<?php

namespace Bermuda\Router;

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
