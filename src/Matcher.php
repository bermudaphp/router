<?php

namespace Bermuda\Router;

interface Matcher
{
    /**
     * @param Route[] $routes
     * @param string $requestMethod
     * @param string $uri
     * @throws Exception\RouteNotFoundException
     * @throws Exception\MethodNotAllowedException
     */
    public function match(iterable $routes, string $requestMethod, string $uri): Route ;
}
