<?php


namespace Bermuda\Router;


use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Fig\Http\Message\RequestMethodInterface;


/**
 * Interface RouteInterface
 * @package Bermuda\Router
 */
interface RouteInterface
{
    /**
     * Names of valid http methods
     */
    public const http_methods = [
        RequestMethodInterface::METHOD_HEAD,
        RequestMethodInterface::METHOD_GET,
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PUT,
        RequestMethodInterface::METHOD_PATCH,
        RequestMethodInterface::METHOD_DELETE,
        RequestMethodInterface::METHOD_PURGE,
        RequestMethodInterface::METHOD_OPTIONS,
        RequestMethodInterface::METHOD_TRACE,
        RequestMethodInterface::METHOD_CONNECT
    ];

    public const tokens = [
        'id' => '\d+',
        'action' => '(create|read|update|delete)',
        'optional' => '/?(.*)'
    ];

    /**
     * Route name
     * @return string
     */
    public function getName() : string ;

    /**
     * @return mixed
     */
    public function getHandler();

    /**
     * @param string $prefix
     * @return Route
     */
    public function addPrefix(string $prefix) : self ;

    /**
     * @param string $suffix
     * @return Route
     */
    public function addSuffix(string $suffix) : self ;

    /**
     * Route path
     * @return string
     */
    public function getPath() : string ;

    /**
     * @return array
     */
    public function tokens(array $tokens = []): array ;

    /**
     * @param array $methods
     * @return array
     */
    public function methods(array $methods = []) : array ;

    /**
     * Route attributes from query string
     * @return array
     */
    public function getAttributes() : array ;

    /**
     * @param array $attributes
     * @return RouteInterface
     */
    public function withAttributes(array $attributes) : RouteInterface ;
    
    /**
     * @param mixed $middleware
     * @return RouteInterface
     */
    public function before($middleware) : RouteInterface ;
    
     /**
     * @param mixed $middleware
     * @return RouteInterface
     */
    public function after($middleware) : RouteInterface ;
}
