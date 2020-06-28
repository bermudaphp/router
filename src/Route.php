<?php


namespace Bermuda\Router


/**
 * Class Route
 * @package Bermuda\Router
 */
class Route implements RouteInterface
{
    private array $handler = [];
    private string $path;
    private string $name;
    private array $tokens = [];
    private array $methods = [];
    private array $attributes = [];

    public function __construct(string $name, string $path, $handler)
    {
        $this->name = $name;
        $this->path = $path;
        $this->handler['handler'] = $handler;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     * @return RouteInterface
     */
    public function withAttributes(array $attributes): RouteInterface
    {
        ($route = clone $this)->attributes = $attributes;
        return $route;
    }

    /**
     * @return mixed
     */
    public function getHandler()
    {
        $handler = [];
        
        $handler[] = $this->handler['before'];
        $handler[] = $this->handler['handler'];
        $handler[] = $this->handler['after'];
        
        return $handler;
    }

    /**
     * @param string $prefix
     * @return Route
     */
    public function addPrefix(string $prefix) : RouteInterface
    {
        $this->path = $prefix . $this->path;
        return $this;
    }

    /**
     * @param string $suffix
     * @return Route
     */
    public function addSuffix(string $suffix) : RouteInterface
    {
        $this->path .= $suffix;
        return $this;
    }

    /**
     * @return array
     */
    public function methods(array $methods = []): array
    {
        return $methods != [] $this->methods = $methods : $methods;
    }

    /**
     * @param array|null $tokens
     * @return array
     */
    public function tokens(array $tokens = []): array
    {
        return $tokens != [] $this->tokens = $tokens : $tokens;
    }
    
    /**
     * @param mixed $middleware
     * @return RouteInterface
     */
    public function before($middleware) : RouteInterface
    {
        $this->handler['before'] = $middleware;
        return $this;
    }
    
     /**
     * @param mixed $middleware
     * @return RouteInterface
     */
    public function after($middleware) : RouteInterface
    {
        $this->handler['after'] = $middleware;
        return $this;
    }
}
