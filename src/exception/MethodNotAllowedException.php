<?php


namespace Bermuda\Router\Exception;


use Throwable;
use Bermuda\Router\RouteInterface;


/**
 * Class MethodNotAllowedException
 * @package Bermuda\Router\Exception
 */
final class MethodNotAllowedException extends RouterException
{
    private RouteInterface $route;
    private string $requestMethod;

    public function __construct(RouteInterface $route, string $requestMethod)
    {
        $this->route = $route;
        $this->requestMethod = $requestMethod;
        parent::__construct(sprintf('The http method : %s for path: %s not allowed. Allows methods: %s.', 
            $requestMethod, $route->getPath(), implode(', ', $route->methods())
        ), 405);
    }

    /**
     * @param RouteInterface $route
     * @param string $requestMethod
     * @return static
     */
    public static function make(RouteInterface $route, string $requestMethod): self
    {
        return new self($route, $requestMethod);
    }

    /**
     * @return RouteInterface
     */
    public function getRoute(): RouteInterface
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }
}
