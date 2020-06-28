<?php


namespace Bermuda\Router;


/**
 * Class RouteFactoryInterface
 * @package Bermuda\Router
 */
interface RouteFactoryInterface
{    
    /**
     * @param array $routeData
     * @return RouteInterface
     */
    public function make(array $routeData): RouteInterface ;
}
