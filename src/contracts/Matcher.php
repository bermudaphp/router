<?php


namespace Lobster\Routing\Contracts;


use Psr\Http\Message\ServerRequestInterface;
use Lobster\Routing\Exceptions\MethodNotAllowedException;
use Lobster\Routing\Exceptions\RouteNotFoundException;


/**
 * Interface Matcher
 * @package Lobster\Routing\Contracts
 */
interface Matcher
{
    /**
     * @param string $method
     * @param string $uri
     * @throws RouteNotFoundException
     * @throws MethodNotAllowedException
     */
    public function match(string $method, string $uri): Route ;
}
