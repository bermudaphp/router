<?php

namespace Bermuda\Router;

interface Generator
{
    /**
     * @throws Exception\GeneratorException
     */
    public function generate(RouteMap $routes, string $name, array $params = []): string ;
}
