<?php


namespace Bermuda\Router;


use Psr\Http\Message\ServerRequestInterface;


/**
 * Interface MatcherInterface
 * @package Bermuda\Router
 */
interface MatcherInterface
{
    /**
     * @param string $method
     * @param string $uri
     * @throws Exception\RouteNotFoundException
     * @throws Exception\MethodNotAllowedException
     */
    public function match(string $method, string $uri): RouteInterface ;
}
