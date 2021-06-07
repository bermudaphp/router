<?php

namespace Bermuda\Router;

use Bermuda\Arrayable;

/**
 * Interface RouteMap
 * @package Bermuda\Router
 */
interface RouteMap extends Arrayable
{
    /**
     * @return Route[]
     */
    public function toArray(): array ;
    
    /**
     * @param Route|array $route
     * @return RouteMap
     */
    public function add($route): RouteMap ;
     
    /**
     * @param string|array $prefix
     * @param callable $callback
     * @return RouteMap
     */
    public function group($prefix, callable $callback): RouteMap ;
    
    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function get($name, ?string $path = null, $handler = null): RouteMap ;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @return RouteMap
     */
    public function post($name, ?string $path = null, $handler = null): RouteMap ;
  
    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function delete($name, ?string $path = null, $handler = null): RouteMap ;
    
    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function put($name, ?string $path = null, $handler = null): RouteMap ;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function patch($name, ?string $path = null, $handler = null): RouteMap ;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function options($name, ?string $path = null, $handler = null): RouteMap ;

    /**
     * @param string|array $name
     * @param string|null $path
     * @param $handler
     * @return RouteMap
     */
    public function any($name, ?string $path = null, $handler = null): RouteMap ;
    
    /**
     * @param string|array $name
     * @param string|null $path
     * @param ResourceInterface|string $resource
     * @return RouteMap
     */
    public function resource($name, ?string $path = null, $resource = null): RouteMap ;
}
