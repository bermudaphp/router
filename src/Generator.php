<?php

namespace Bermuda\Router;

interface Generator
{
    /**
     * @param RouteMap $routes
     * @param string $name
     * @param array $attributes
     * @return string
     * @throws Exception\RouteNotFoundException
     * @throws Exception\GeneratorException
     */
    public function generate(RouteMap $routes, string $name, array $attributes = []): string ;
}
