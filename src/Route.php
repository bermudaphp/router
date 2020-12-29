<?php

namespace Bermuda\Router;


/**
 * Class Route
 * @package Bermuda\Router
 */
 class Route
{
    protected string $name;
    protected string $path;
    protected array $handler; 
    protected array $tokens = [];
    protected array $methods = [];
    protected array $attributes = [];

    public function __construct(
        string $name, string $path, 
        $handler, ?$methods = null, 
        ?array $middleware = null,
        ?array $tokens = null,
    )
    {
        $this->name = $name;
        $this->path = $path;
        $this->handler = [$handler];
        
        $this->methods($methods);
        $this->tokens($tokens);
        
        if ($middleware != null)
        {
            !isset($middleware['after']) ?: $this->after($middleware['after']);
            !isset($middleware['before']) ?: $this->before($middleware['before']);
        }
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
     * @return self
     */
    public function withAttributes(array $attributes): self
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
     * @return self
     */
    public function withPrefix(string $prefix): self
    {
        $route = clone $this;
        $route->path = $prefix . $this->path;
        
        return $route;
    }

    /**
     * @param array|string|null $methods
     * @return array|self
     */
    public function methods($methods = null)
    {
        if ($methods != null)
        {
            if (is_string($methods) && strpos($methods, '|') !== false)
            {
                $methods = explode('|', $methods);
            }
            
            $route = clone $this;
            $route->methods = array_map('strtoupper', (array) $methods);
        
            return $route;
        }
        
        return $this->methods;
    }

    /**
     * @return array|self
     */
    public function tokens(?array $tokens = null)
    {
        if ($tokens == null)
        {
            return $this->tokens;
        }
        
        $route = clone $this;
        $route->tokens = array_merge($this->tokens, $tokens);
        
        return $route;
    }
    
    /**
     * @param mixed $middleware
     * @return self
     */
    public function before($middleware): self
    {
        $route = clone $this;
        array_unshift($route->handler, $middleware);
        
        return $route;
    }
    
     /**
     * @param mixed $middleware
     * @return self
     */
    public function after($middleware): self
    {
        $route = clone $this;
        array_push($route->handler, $middleware);
        
        return $route;
    }
    
    /**
     * @param array $data
     * @return self
     */
    public static function makeOf(array $data): self
    {
        foreach (['name', 'path', 'handler'] as $key)
        {
            if (!array_key_exists($key, $data))
            {
                throw new \InvalidArgumentException(sprintf('Missing %s $data[\'%s\']', __METHOD__, $key));
            }
        }
        
        return new Route($data['name'], $data['path'], $data['handler'], $data['methods'] ?? self::http_methods, $data['middleware'] ?? null, $data['tokens'] ?? self::tokens);
    }
}
