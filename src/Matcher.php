<?php

namespace Bermuda\Router;

interface Matcher
{
    public function match(RouteMap $routes, string $uri, string $requestMethod):? RouteRecord;
}
