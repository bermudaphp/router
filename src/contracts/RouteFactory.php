<?php


namespace Lobster\Routing\Contracts;


use Lobster\Routing\Exceptions\UnresolvableHandlerException;


/**
 * Class RouteFactory
 * @package Lobster\Routing
 */
interface RouteFactory
{

    /**
     * @param array $routeData
     * @return Route
     */
    public function __invoke(array $routeData): Route;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     * @throws UnresolvableHandlerException
     */
    public function get(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS): Route;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     * @throws UnresolvableHandlerException
     */
    public function post(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS): Route;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     * @throws UnresolvableHandlerException
     */
    public function delete(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS): Route;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     * @throws UnresolvableHandlerException
     */
    public function put(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS): Route;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     * @throws UnresolvableHandlerException
     */
    public function head(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS): Route;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     * @throws UnresolvableHandlerException
     */
    public function options(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS): Route;

    /**
     * @param string $name
     * @param string $path
     * @param $handler
     * @param array $tokens
     * @return Route
     * @throws UnresolvableHandlerException
     */
    public function any(string $name, string $path, $handler, array $tokens = Route::ROUTE_TOKENS): Route;
}