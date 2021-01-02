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
     * @param string $requestMethod
     * @param string $uri
     * @throws Exception\RouteNotFoundException
     * @throws Exception\MethodNotAllowedException
     */
    public function match(string $requestMethod, string $uri): Route ;
}
