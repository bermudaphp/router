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
        self::GET => RequestMethodInterface::METHOD_GET,
        self::POST => RequestMethodInterface::METHOD_POST,
        self::PUT => RequestMethodInterface::METHOD_PUT,
        self::PATCH => RequestMethodInterface::METHOD_PATCH,
        self::DELETE => RequestMethodInterface::METHOD_DELETE,
        self::OPTIONS => RequestMethodInterface::METHOD_OPTIONS,
    ];
    
    public const GET = 0;
    public const POST = 1;
    public const PUT = 2;
    public const PATCH = 3;
    public const DELETE = 4;
    public const OPTIONS = 5;
    public const ANY = self::GET|self::POST|self::PUT|self::PATCH|self::DELETE|self::OPTIONS
        
    public const tokens = [
        'id' => '\d+',
        'action' => '(create|read|update|delete)',
        'optional' => '/?(.*)'
    ];

    /**
     * Route name
     * @return string
     */
    public function getName(): string ;

    /**
     * @return mixed
     */
    public function getHandler();

    /**
     * @param string $prefix
     * @return Route
     */
    public function addPrefix(string $prefix): self ;

    /**
     * @param string $suffix
     * @return Route
     */
    public function addSuffix(string $suffix): self ;

    /**
     * Route path
     * @return string
     */
    public function getPath(): string ;

    /**
     * @return array
     */
    public function tokens(array $tokens = [], bool $replace = false): array ;

    /**
     * @param int|string|null $methods
     * @return array
     */
    public function methods($methods = null): array ;

    /**
     * Route attributes from query string
     * @return array
     */
    public function getAttributes(): array ;

    /**
     * @param array $attributes
     * @return RouteInterface
     */
    public function withAttributes(array $attributes): RouteInterface ;
    
    /**
     * @param mixed $middleware
     * @return RouteInterface
     */
    public function before($middleware): RouteInterface ;
    
     /**
     * @param mixed $middleware
     * @return RouteInterface
     */
    public function after($middleware): RouteInterface ;
}
