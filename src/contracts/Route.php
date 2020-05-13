<?php


namespace Lobster\Routing\Contracts;


use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Fig\Http\Message\RequestMethodInterface;


/**
 * Interface Route
 * @package Lobster\Routing\Contracts
 */
interface Route
{
    /**
     * Names of valid http methods
     */
    public const HTTP_METHODS = [
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

    public const ROUTE_TOKENS = [
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
    public function tokens(array $tokens = []): array;

    /**
     * @return array
     */
    public function getMethods() : array ;

    /**
     * Route attributes from query string
     * @return array
     */
    public function getAttributes() : array ;

    /**
     * @param array $attributes
     * @return Route
     */
    public function withAttributes(array $attributes) : Route ;
}
