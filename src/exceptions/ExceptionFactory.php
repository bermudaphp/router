<?php


namespace Lobster\Routing\Exceptions;


/**
 * Class RouterException
 * @package Lobster\Routing\Exceptions
 */
final class ExceptionFactory
{

    /**
     * @param string $method
     * @param array $methods
     * @return MethodNotAllowedException
     */
    public static function notAllows(string $method, array $allowed) : MethodNotAllowedException
    {
         return new MethodNotAllowedException(sprintf('The http method : %s not allowed. Allows methods: %s.', $method, implode(', ', $allowed)), 405);
    }

    /**
     * @return Exception
     */
    public static function emptyHandler() : Exception
    {
        return new Exception('Route handler is empty.');
    }
    
    /**
     * @param string $path
     * @return RouteNotFoundException
     */
    public static function notFound() : RouteNotFoundException
    {
        return new RouteNotFoundException('', 404);
    }

    /**
     * @param string $attribute
     * @return GeneratorException
     */
    public static function pathAttributeMissing(string $attribute) : GeneratorException
    {
       return new GeneratorException('Missing attribute with name: ' . $attribute);
    }
}
