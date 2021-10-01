<?php

namespace Bermuda\Router;

interface Generator
{
    /**
     * @param Route[] $routes
     * @param string $name
     * @param array $attributes
     * @return string
     * @throws Exception\RouteNotFoundException
     * @throws Exception\GeneratorException
     */
    public function generate(iterable $routes, string $name, array $attributes = []): string ;
}
