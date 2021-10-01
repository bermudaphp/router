<?php

namespace Bermuda\Router;

interface Matcher
{
    /**
     * @param RouteMap $routes
     * @param string $requestMethod
     * @param string $uri
     * @throws Exception\RouteNotFoundException
     * @throws Exception\MethodNotAllowedException
     */
    public function match(RouteMap $routes, string $requestMethod, string $uri): Route ;
}
