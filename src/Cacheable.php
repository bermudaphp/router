<?php

namespace Bermuda\Router;

interface Cacheable
{
    /**
     * @param string $filename
     * @param callable|null $fileWriter
     * @return void
     */
    public function cache(string $filename, callable $fileWriter = null): void ;

    /**
     * @param string $filename
     * @param array|null $context
     * @return RouteMap
     */
    public static function createFromCache(string $filename, array $context = null): RouteMap ;
}
