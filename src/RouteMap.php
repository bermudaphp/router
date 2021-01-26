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
     * @return RouteInterface[]
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
     * @param array $mutators
     * @return RouteMap
     */
    public function group($prefix, callable $callback): RouteMap ;
    
    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function get(string $name, string $path, $handler, array $mutators = []): RouteMap ;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function post(string $name, string $path, $handler, array $mutators = []): RouteMap ;
  
    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function delete(string $name, string $path, $handler, array $mutators = []): RouteMap ;
    
    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function put(string $name, string $path, $handler, array $mutators = []): RouteMap ;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function patch(string $name, string $path, $handler, array $mutators = []): RouteMap ;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function options(string $name, string $path, $handler, array $mutators = []): RouteMap ;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $mutators
     * @return RouteMap
     */
    public function any(string $name, string $path, $handler, array $mutators = []): RouteMap ;
}
