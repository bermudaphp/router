<?php

namespace Bermuda\Router;

function build(string $name, string $path, mixed $handler): RouteBuilder
{
    return new RouteBuilder($name, $path, $handler);
}
