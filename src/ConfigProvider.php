<?php


namespace Bermuda\Router;


final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'invokables' => [
                    RouterInterface::class => Router::class
                ],
                'aliases' => [
                    GeneratorInterface::class => RouterInterface::class,
                    MatcherInterface::class => RouterInterface::class,
                    RouteMap::class => RouterInterface::class
                ]
            ]
        ];
    }
}
