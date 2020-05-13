<?php


namespace Lobster\Routing\Contracts;


use Lobster\Routing\RouteMap;


/**
 * Interface Router
 * @package Lobster\Routing\Contracts
 */
interface Router extends Matcher, Generator
{

    /**
     * @return RouteMap
     */
    public function getRoutes() : RouteMap;
}
