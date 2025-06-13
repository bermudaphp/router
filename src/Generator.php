<?php

namespace Bermuda\Router;

use Bermuda\Router\Exception\GeneratorException;
use Bermuda\Router\Exception\RouteNotRegisteredException;

/**
 * URL Generator Interface
 *
 * Defines the contract for URL generation from route definitions.
 * Converts route names and parameters into URI paths for use in applications.
 *
 * @package Bermuda\Router
 */
interface Generator
{
    /**
     * Generate a URI path for the specified named route.
     *
     * Takes a route name and optional parameters to construct a complete URI path
     * by locating the route in the RouteMap and substituting parameters with provided values.
     *
     * @param RouteMap $routes The route map containing route definitions
     * @param string $name The name of the route to generate URI path for
     * @param array $params Associative array of parameters to substitute in the route pattern
     * @return string The generated URI path for the specified route
     * @throws GeneratorException If required parameters are missing, parameter values are invalid,
     *                           or other generation-related errors occur
     * @throws RouteNotRegisteredException If the specified route name is not registered in the route map
     */
    public function generate(RouteMap $routes, string $name, array $params = []): string;
}