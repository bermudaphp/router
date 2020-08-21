<?php


namespace Bermuda\Router;


/**
 * Class Route
 * @package Bermuda\Router
 */
final class Route implements RouteInterface
{
    private string $name;
    private string $path;
    private array $handler; 
    private array $tokens = [];
    private array $methods = [];
    private array $attributes = [];

    public function __construct(string $name, string $path, $handler)
    {
        $this->name = $name;
        $this->path = $path;
        $this->handler = [$handler];
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
        $route = clone $this;
        $route->attributes = $attributes;
        return $route;
    }

    /**
     * @return mixed
     */
    public function getHandler()
    {
        return count($this->handler) > 1 ? $this->handler : $this->handler[0];
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
    public function methods($methods = null): array
    {
        if ($methods == null)
        {
            return $this->methods;
        }
        
        if (is_string($methods))
        {
            $methods = strpos($methods, '|') !== false ? 
                explode('|', $methods) : (array) strtoupper($methods);

            goto setArray;
        }
        
        if (is_array($methods))
        {
            setArray:
            
            foreach ($methods as $method)
            {
                $this->methods[] = strtoupper($method);
            }
            
            return $this->methods;
        }
        
       if (is_int($methods))
       {
            foreach (self::ANY as $mask => $method)
            {
                if ($methods & $mask)
                {
                    $this->methods[] = $method
                }
            }
       }
                
       return $this->methods;
    }

    /**
     * @param array|null $tokens
     * @return array
     */
    public function tokens(array $tokens = [], bool $replace = false): array
    {
        if($replace)
        {
            return $tokens != [] ? $this->tokens = $tokens : $this->tokens;
        }
        
        return $tokens != [] ? $this->tokens = array_merge($this->tokens, $tokens) : $this->tokens;
    }
    
    /**
     * @param mixed $middleware
     * @return RouteInterface
     */
    public function before($middleware) : RouteInterface
    {
        array_unshift($this->handler, $middleware);
        return $this;
    }
    
     /**
     * @param mixed $middleware
     * @return RouteInterface
     */
    public function after($middleware) : RouteInterface
    {
        array_push($this->handler, $middleware);
        return $this;
    }
}
