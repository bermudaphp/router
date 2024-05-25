<?php

namespace Bermuda\Router;

interface Cacheable
{
    public function cache(string $filename, callable $fileWriter = null): void ;
    public static function createFromCache(string $filename, array $context = null): RouteMap ;
}
