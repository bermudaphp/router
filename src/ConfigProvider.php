<?php


namespace Bermuda\Router;


final class ConfigProvider
{
    public function __invoke(): array
    {
        return ['dependencies' => ['factories' => [RouterInterface::class => function(){return new Router();}]]];
    }
}
